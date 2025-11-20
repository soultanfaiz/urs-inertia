<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\VerificationStatus;
use App\Models\AppRequest;
use App\Models\RequestDocSupport;
use App\Models\RequestHistory;
use App\Models\RequestImageSupport;
use App\Enums\RequestStatus;
use App\Enums\Instansi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia; // Import Inertia

class AppRequestController extends Controller
{
    /**
     * Menampilkan daftar permohonan.
     */
    public function index()
    {
        // Ambil data permohonan berdasarkan role
        $appRequestsQuery = AppRequest::with('user')->latest();

        if (!auth()->user()->hasRole('admin')) {
            $appRequestsQuery->where('user_id', auth()->id());
        }

        $appRequests = $appRequestsQuery->paginate(10)->withQueryString();

        // Render komponen Vue 'AppRequest/Index' dan kirimkan data sebagai props
        return Inertia::render('AppRequest/Index', [
            'appRequests' => $appRequests,
        ]);
    }

    /**
     * Menampilkan form untuk membuat permohonan baru.
     */
    public function create()
    {
        // Render komponen Vue 'AppRequest/Create'
        return Inertia::render('AppRequest/Create', [
            'instansi' => collect(Instansi::cases())->map(fn ($instansi) => [
                'value' => $instansi->value,
                'label' => $instansi->value,
            ]),
        ]);
    }

    /**
     * Menyimpan permohonan baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'instansi' => ['required', new Enum(Instansi::class)],
            'file_pdf' => 'required|file|mimes:pdf|max:2048',
        ]);

        $path = $request->file('file_pdf')->store('pdfs');

        $appRequest = AppRequest::create([
            'user_id' => auth()->id(),
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'start_date' => now(), // Set default start date
            'end_date' => now()->addMonth(),   // Set default end date
            'instansi' => $validatedData['instansi'],
            'file_path' => $path,
            'status' => RequestStatus::PERMOHONAN,
            'verification_status' => VerificationStatus::MENUNGGU,
        ]);

        $appRequest->histories()->create([
            'user_id' => auth()->id(),
            'status' => RequestStatus::PERMOHONAN,
        ]);

        return redirect()->route('app-requests.index')->with('success', 'Permohonan berhasil diajukan!');
    }

    /**
     * Menampilkan detail permohonan.
     */
    public function show(AppRequest $appRequest)
    {
        if (auth()->user()->id !== $appRequest->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan untuk melihat permohonan ini.');
        }

        $appRequest->load([
            'user',
            'histories.user', // Muat juga user yang membuat history
            'histories.docSupports',
            'histories.imageSupports',
            'developmentActivities.subActivities'
        ]);

        // Render komponen Vue 'AppRequest/Show'
        return Inertia::render('AppRequest/Show', [
            'appRequest' => $appRequest,
        ]);
    }

    // ... (Metode lain seperti download, verify, updateStatus, dll. tetap sama)
    // ... karena mereka sudah menggunakan redirect atau response langsung,
    // ... yang kompatibel dengan cara kerja Inertia.
    // ... Pastikan Anda menyalin semua metode lainnya dari controller asli Anda ke sini.
    // ... Saya akan menyalin beberapa metode penting sebagai contoh.

    /**
     * Mengunduh file PDF utama.
     */
    public function download(AppRequest $appRequest)
    {
        if (auth()->user()->id !== $appRequest->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses file ini.');
        }
        if (!$appRequest->file_path || !Storage::exists($appRequest->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }
        return Storage::response(
            $appRequest->file_path,
            basename($appRequest->file_path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . basename($appRequest->file_path) . '"']
        );
    }

    /**
     * Memverifikasi permohonan (oleh admin).
     */
    public function verify(Request $request, AppRequest $appRequest)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'verification_status' => ['required', new Enum(VerificationStatus::class)],
            'reason' => 'nullable|string|required_if:verification_status,' . VerificationStatus::DITOLAK->value,
        ]);

        DB::transaction(function () use ($appRequest, $validated) {
            $verificationStatus = VerificationStatus::from($validated['verification_status']);
            $appRequest->verification_status = $verificationStatus;

            $appRequest->histories()->create([
                'user_id' => auth()->id(),
                'status' => $verificationStatus->value,
                'reason' => $validated['reason'] ?? null,
            ]);

            if ($verificationStatus === VerificationStatus::DISETUJUI) {
                $allStatuses = RequestStatus::cases();
                $currentIndex = array_search($appRequest->status, $allStatuses, true);
                if ($currentIndex !== false && isset($allStatuses[$currentIndex + 1])) {
                    $nextStatus = $allStatuses[$currentIndex + 1];
                    $appRequest->status = $nextStatus;
                    $appRequest->histories()->create([
                        'user_id' => auth()->id(),
                        'status' => $nextStatus->value,
                        'reason' => 'Status otomatis naik setelah verifikasi disetujui.',
                    ]);
                }
            }

            if ($verificationStatus === VerificationStatus::DITOLAK) {
                $appRequest->status = RequestStatus::PERMOHONAN;
            }

            $appRequest->save();
        });

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Status permohonan berhasil diperbarui!');
    }

    // ... (Pastikan semua metode lain ada di sini)
}
