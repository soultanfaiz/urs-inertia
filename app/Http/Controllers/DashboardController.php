<?php

namespace App\Http\Controllers;

use App\Enums\Instansi;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Models\AppRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman laporan infografis menggunakan Inertia.
     */
    public function index()
    {
        // --- Statistik Utama ---
        $stats = [
            'totalRequests'       => AppRequest::count(),
            'completedRequests'   => AppRequest::where('status', RequestStatus::SELESAI)->count(),
            'pendingVerification' => AppRequest::where('verification_status', VerificationStatus::MENUNGGU)->count(),
            'rejectedRequests'    => AppRequest::where('verification_status', VerificationStatus::DITOLAK)->count(),
        ];

        // --- Data untuk Grafik ---

        // 1. Permohonan berdasarkan Status Progres
        $requestsByStatus = AppRequest::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                // Menggunakan ->label() jika ada di Enum Anda, jika tidak ganti dengan ->value
                return [$item->status->label() => $item->total];
            });

        // 2. Permohonan berdasarkan Instansi
        $requestsByInstansi = AppRequest::query()
            ->select('instansi', DB::raw('count(*) as total'))
            ->groupBy('instansi')
            ->get()
            ->mapWithKeys(function ($item) {
                // Menggunakan ->value dari Enum
                return [$item->instansi->value => $item->total];
            });

        // 3. Permohonan Masuk per Bulan (dalam 12 bulan terakhir)
        $requestsOverTime = AppRequest::query()
            ->select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month, count(*) as total")
            )
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                // Format bulan menjadi "Jan 2023"
                return [Carbon::createFromFormat('Y-m', $item->month)->format('M Y') => $item->total];
            });

        // Render komponen Vue 'Dashboard' dan kirimkan data sebagai props
        return Inertia::render('Dashboard', [
            'stats'  => $stats,
            'charts' => [
                'requestsByStatus'   => $requestsByStatus,
                'requestsByInstansi' => $requestsByInstansi,
                'requestsOverTime'   => $requestsOverTime,
            ]
        ]);
    }
}
