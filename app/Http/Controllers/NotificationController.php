<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        // Mengambil notifikasi secara langsung dari model Notification
        // berdasarkan user_id dari pengguna yang terautentikasi.
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(15); // Anda bisa sesuaikan jumlah paginasi

        return Inertia::render('Notification/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        $user = Auth::user();

        // Otorisasi: Pastikan notifikasi ini milik user yang sedang login.
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        // Jika notifikasi punya link, redirect ke sana. Jika tidak, kembali ke halaman sebelumnya.
        if ($notification->link) {
            return redirect($notification->link);
        }

        return back()->with('success', 'Notifikasi ditandai sebagai telah dibaca.');
    }

    /**
     * Mark all unread notifications as read for the current user.
     */
    public function markAllAsRead(): RedirectResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}
