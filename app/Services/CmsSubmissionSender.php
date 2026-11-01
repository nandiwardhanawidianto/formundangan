<?php

namespace App\Services;

use App\Models\WeddingOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CmsSubmissionSender
{
    public function send(WeddingOrder $order, InvitationExporter $exporter): array
    {
        if ($order->product_type !== 'digital') {
            return ['status' => 'not_applicable', 'message' => 'Undangan cetak tidak dikirim ke CMS digital.'];
        }

        $url = trim((string) config('royal.cms_submission_url'));
        $token = trim((string) config('royal.cms_integration_token'));
        if (!$url || !$token || !str_starts_with($url, 'https://')) {
            $order->forceFill(['cms_sync_status' => 'not_configured', 'cms_sync_error' => 'Alamat HTTPS CMS atau token integrasi belum dikonfigurasikan.'])->save();
            return ['status' => 'not_configured', 'message' => 'Integrasi CMS belum dikonfigurasikan di server.'];
        }

        try {
            $order->loadCount('photos');
            $response = Http::acceptJson()->withHeaders([
                'X-CMS-Integration-Token' => $token,
            ])->connectTimeout(5)->timeout(15)->post($url, [
                'order_number' => $order->order_number,
                'customer_whatsapp' => $order->customer_whatsapp,
                'shopee_username' => $order->shopee_username,
                'display_order' => $order->display_order,
                'photo_count' => $order->photos_count,
                'photo_package' => $order->photo_package,
                'payload' => $exporter->data($order),
                'raw_form' => $order->payload ?? [], // data asli, termasuk kolom opsional yang belum lengkap
            ]);

            if ($response->successful() && $response->json('status') === 'pending_review') {
                $order->forceFill([
                    'cms_sync_status' => 'pending_review',
                    'cms_sync_error' => null,
                    'cms_synced_at' => now(),
                ])->save();
                return ['status' => 'pending_review', 'message' => 'Data digital sudah masuk antrean review CMS. Belum dipublikasikan.'];
            }

            if ($response->status() === 409 && $response->json('status') === 'review_locked') {
                $order->forceFill([
                    'cms_sync_status' => 'review_locked',
                    'cms_sync_error' => null,
                ])->save();
                return [
                    'status' => 'review_locked',
                    'message' => 'Pengajuan sudah ditinjau atau diterbitkan di CMS. Perubahan berikutnya perlu diperiksa admin langsung di CMS.',
                ];
            }

            $error = 'CMS menolak pengiriman (HTTP '.$response->status().').';
            $order->forceFill(['cms_sync_status' => 'failed', 'cms_sync_error' => $error])->save();
            return ['status' => 'failed', 'message' => $error.' Admin dapat mencoba kirim ulang.'];
        } catch (Throwable $e) {
            Log::warning('Pengiriman formulir ke CMS gagal', ['order_id' => $order->id, 'exception' => $e::class]);
            $order->forceFill(['cms_sync_status' => 'failed', 'cms_sync_error' => 'Koneksi CMS gagal.'])->save();
            return ['status' => 'failed', 'message' => 'Koneksi CMS gagal. Data tetap tersimpan dan admin dapat mencoba kirim ulang.'];
        }
    }
}
