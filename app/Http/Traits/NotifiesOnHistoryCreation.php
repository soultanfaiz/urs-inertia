<?php

namespace App\Http\Traits;

use App\Models\AppRequest;
use App\Models\RequestHistory;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Str; // <--- 1. TAMBAHKAN IMPORT INI

trait NotifiesOnHistoryCreation
{
    /**
     * Membuat notifikasi untuk pengguna yang relevan berdasarkan riwayat permohonan.
     *
     * @param AppRequest $appRequest
     * @param RequestHistory $history
     * @return void
     */
    protected function sendNotificationForHistory(AppRequest $appRequest, RequestHistory $history): void
    {
        // Tentukan judul notifikasi berdasarkan tipe riwayat
        $title = 'Pembaruan Permohonan';
        if ($history->type === 'verification') {
            $title = 'Hasil Verifikasi Permohonan';
        }

        // Buat pesan notifikasi yang deskriptif
        $message = "Status pada permohonan '{$appRequest->title}' telah diperbarui menjadi '{$history->status->label()}'";

        // Dapatkan semua admin dan semua pengguna dari instansi terkait
        $usersToNotify = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->orWhere('instansi', $appRequest->instansi)->get();

        $notifications = [];
        $now = now();

        foreach ($usersToNotify as $user) {
            $notifications[] = [
                'id' => (string) Str::uuid(),
                'request_history_id' => $history->id,
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'link' => route('app-requests.show', $appRequest->id),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Masukkan semua notifikasi ke database dalam satu query
        if (!empty($notifications)) {
            Notification::insert($notifications);
        }
    }
}
