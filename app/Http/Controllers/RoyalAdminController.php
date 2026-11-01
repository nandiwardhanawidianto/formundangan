<?php

namespace App\Http\Controllers;

use App\Models\WeddingOrder;
use App\Models\WeddingPhoto;
use App\Services\InvitationExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RoyalAdminController extends Controller
{
    private function requireAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->is_admin, 403);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (!Auth::attempt($data) || !Auth::user()->is_admin) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }
        $request->session()->regenerate();
        return response()->json(['ok' => true]);
    }

    public function me(): JsonResponse
    {
        $this->requireAdmin();
        return response()->json(['name' => Auth::user()->name]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->requireAdmin();
        $query = WeddingOrder::query()->withCount('photos')->orderByDesc('updated_at');
        if ($request->filled('q')) $query->where('order_number', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->input('q')).'%');
        return response()->json($query->paginate(25));
    }

    public function show(WeddingOrder $order): JsonResponse
    {
        $this->requireAdmin();
        return response()->json(['order' => $order->load('photos')]);
    }

    public function notifications(): JsonResponse
    {
        $this->requireAdmin();
        $unread = WeddingOrder::whereNotNull('submitted_at')->whereNull('notification_seen_at');
        return response()->json([
            'unread_count' => (clone $unread)->count(),
            'orders' => $unread->latest('submitted_at')->limit(10)->get(['id','order_number','submitted_at','status']),
        ]);
    }

    public function markSeen(WeddingOrder $order): JsonResponse
    {
        $this->requireAdmin();
        $order->update(['notification_seen_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function update(Request $request, WeddingOrder $order): JsonResponse
    {
        $this->requireAdmin();
        $data = $request->validate([
            'status' => ['sometimes', 'in:draft,submitted,needs_revision,approved'],
            'shopee_username' => ['sometimes', 'nullable', 'string', 'max:120'],
            'payload' => ['sometimes', 'array'],
            'payload.love_gift' => ['sometimes', 'array', 'max:3'],
            'payload.love_gift.*.bank' => ['nullable', 'string', 'max:120'],
            'payload.love_gift.*.account_number' => ['nullable', 'string', 'max:120'],
            'payload.love_gift.*.account_name' => ['nullable', 'string', 'max:120'],
            'customer_whatsapp' => ['sometimes', 'string', 'max:20'],
        ]);
        if (($data['status'] ?? null) === 'approved') $data['reviewed_at'] = now();
        $order->update($data);
        return response()->json(['order' => $order->fresh('photos')]);
    }

    public function destroy(WeddingOrder $order): JsonResponse
    {
        $this->requireAdmin();
        $order->delete(); // Reversible soft delete. Photos retained until purge policy is established.
        return response()->json(['ok' => true]);
    }

    public function export(WeddingOrder $order, InvitationExporter $exporter)
    {
        $this->requireAdmin();
        abort_unless($order->status === 'approved', 403, 'Setujui pesanan sebelum mengunduh file produksi.');
        return $order->product_type === 'digital'
            ? response($exporter->json($order), 200, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="undangan-'.$order->id.'.json"',
              ])
            : response($exporter->jsx($order), 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="undangan-'.$order->id.'.jsx"',
              ]);
    }

    public function photo(WeddingOrder $order, WeddingPhoto $photo)
    {
        $this->requireAdmin();
        abort_unless($photo->wedding_order_id === $order->id, 404);
        return Storage::disk('local')->download($photo->storage_path, $photo->original_name);
    }
}
