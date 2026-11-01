<?php

namespace App\Http\Controllers;

use App\Models\WeddingOrder;
use App\Models\WeddingPhoto;
use App\Services\CmsSubmissionSender;
use App\Services\InvitationExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WeddingOrderController extends Controller
{
    private function normalizeWhatsApp(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($clean, '+62')) $clean = '0'.substr($clean, 3);
        elseif (str_starts_with($clean, '62')) $clean = '0'.substr($clean, 2);
        if (!preg_match('/^08[0-9]{8,11}$/', $clean)) {
            throw ValidationException::withMessages(['customer_whatsapp' => 'Masukkan nomor WhatsApp Indonesia yang valid.']);
        }
        return $clean;
    }

    private function viewOrder(WeddingOrder $order): array
    {
        $order->load('photos:id,wedding_order_id,original_name,file_size,mime_type');
        return $order->toArray();
    }

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{6,80}$/'],
            'customer_whatsapp' => ['required', 'string', 'max:30'],
        ]);
        $whatsapp = $this->normalizeWhatsApp($data['customer_whatsapp']);
        $order = DB::transaction(function () use ($data, $whatsapp) {
            $order = WeddingOrder::firstOrCreate(
                ['order_number' => $data['order_number']],
                ['customer_whatsapp' => $whatsapp, 'payload' => []]
            );
            // WhatsApp is contact information, not authentication.
            if ($order->customer_whatsapp !== $whatsapp) {
                $order->update(['customer_whatsapp' => $whatsapp]);
            }
            return $order;
        });
        return response()->json(['order' => $this->viewOrder($order)]);
    }

    public function save(Request $request, string $orderNumber): JsonResponse
    {
        // V6 intentionally uses only the order number as access key; see SECURITY.md.
        $order = WeddingOrder::where('order_number', $orderNumber)->firstOrFail();
        $data = $request->validate([
            'shopee_username' => ['nullable', 'string', 'max:120'],
            'product_type' => ['nullable', 'in:digital,print'],
            'photo_package' => ['nullable', 'in:with_photo,no_photo'],
            'display_order' => ['nullable', 'in:female_first,male_first'],
            'payload' => ['nullable', 'array'],
            'payload.bride' => ['sometimes', 'array'],
            'payload.groom' => ['sometimes', 'array'],
            'payload.events' => ['sometimes', 'array', 'max:3'],
            'payload.love_gift' => ['sometimes', 'array', 'max:3'],
            'payload.love_gift.*.bank' => ['nullable', 'string', 'max:120'],
            'payload.love_gift.*.account_number' => ['nullable', 'string', 'max:120'],
            'payload.love_gift.*.account_name' => ['nullable', 'string', 'max:120'],
            'payload.events.*.date' => ['nullable', 'date_format:Y-m-d'],
            'payload.events.*.maps' => ['nullable', 'string', 'max:2048'],
        ]);
        $data['photo_package'] = ($data['product_type'] ?? $order->product_type) === 'digital'
            ? ($data['photo_package'] ?? $order->photo_package) : null;
        $changed = count($data) > 0;
        $order->fill($data);
        if ($order->product_type === 'digital' && $order->cms_sync_status === 'pending_review' && $order->isDirty()) {
            $order->cms_sync_status = 'outdated';
            $order->cms_sync_error = 'Data berubah setelah dikirim ke CMS; kirim ulang setelah review.';
        }
        if ($changed && in_array($order->status, ['approved','submitted'], true) && $order->isDirty()) {
            $order->status = 'needs_revision';
            $order->reviewed_at = null;
            $order->notification_seen_at = null;
        }
        $order->save();
        return response()->json(['order' => $this->viewOrder($order)]);
    }

    public function submit(string $orderNumber, CmsSubmissionSender $cms, InvitationExporter $exporter): JsonResponse
    {
        $order = WeddingOrder::where('order_number', $orderNumber)->firstOrFail();
        $order->status = 'submitted';
        $order->submitted_at = now();
        $order->notification_seen_at = null;
        $order->reviewed_at = null;
        $order->save();
        // Simpan pengajuan terlebih dahulu; kegagalan CMS tidak membatalkan pesanan/WhatsApp.
        $sync = $cms->send($order, $exporter);
        $note = "Halo Admin Royal Wedding Invitation.\n\nSaya sudah mengisi data undangan melalui website.\nNomor Pesanan Shopee: {$order->order_number}\n\nData saya sudah tersimpan. Mohon diperiksa dan diberi tahu jika perlu perbaikan atau kelengkapan data.\nTerima kasih.";
        $admin = preg_replace('/\D/', '', config('royal.admin_whatsapp', ''));
        if (str_starts_with($admin, '0')) $admin = '62'.substr($admin, 1);
        return response()->json([
            'order' => $this->viewOrder($order),
            'whatsapp_url' => $admin ? 'https://wa.me/'.$admin.'?text='.rawurlencode($note) : null,
            'notice' => 'Data tersimpan. '.$sync['message'].' Anda masih perlu menekan Kirim di WhatsApp.',
            'cms_sync_status' => $sync['status'],
        ]);
    }

    public function upload(Request $request, string $orderNumber): JsonResponse
    {
        $order = WeddingOrder::where('order_number', $orderNumber)->firstOrFail();
        if ($order->product_type !== 'digital' || $order->photo_package !== 'with_photo') {
            throw ValidationException::withMessages(['photos' => 'Unggah foto hanya tersedia untuk undangan digital dengan foto.']);
        }
        $data = $request->validate(['photos' => ['required','array','max:10'], 'photos.*' => ['required','file','mimes:jpg,jpeg,png','max:7168']]);
        $files = $data['photos'];
        $existing = $order->photos()->count();
        if ($existing + count($files) > 10) {
            throw ValidationException::withMessages(['photos' => 'Jumlah keseluruhan foto maksimal 10.']);
        }
        foreach ($files as $file) {
            $path = $file->store('wedding-photos/'.$order->id, 'local');
            $order->photos()->create([
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
        if (in_array($order->status, ['approved', 'submitted'], true)) {
            $order->update([
                'status' => 'needs_revision',
                'reviewed_at' => null,
                'notification_seen_at' => null,
                'cms_sync_status' => $order->cms_sync_status === 'pending_review' ? 'outdated' : $order->cms_sync_status,
                'cms_sync_error' => $order->cms_sync_status === 'pending_review' ? 'Jumlah foto berubah; kirim ulang data ke CMS.' : $order->cms_sync_error,
            ]);
        }
        return response()->json(['order' => $this->viewOrder($order)]);
    }

    public function photo(string $orderNumber, WeddingPhoto $photo)
    {
        $order = WeddingOrder::where('order_number', $orderNumber)->firstOrFail();
        abort_unless($photo->wedding_order_id === $order->id, 404);
        return Storage::disk('local')->response($photo->storage_path, $photo->original_name, ['Content-Type' => $photo->mime_type]);
    }

    public function removePhoto(string $orderNumber, WeddingPhoto $photo): JsonResponse
    {
        $order = WeddingOrder::where('order_number', $orderNumber)->firstOrFail();
        abort_unless($photo->wedding_order_id === $order->id, 404);
        Storage::disk('local')->delete($photo->storage_path);
        $photo->delete();
        if (in_array($order->status, ['approved', 'submitted'], true)) {
            $order->update([
                'status' => 'needs_revision',
                'reviewed_at' => null,
                'notification_seen_at' => null,
                'cms_sync_status' => $order->cms_sync_status === 'pending_review' ? 'outdated' : $order->cms_sync_status,
                'cms_sync_error' => $order->cms_sync_status === 'pending_review' ? 'Jumlah foto berubah; kirim ulang data ke CMS.' : $order->cms_sync_error,
            ]);
        }
        return response()->json(['order' => $this->viewOrder($order)]);
    }
}
