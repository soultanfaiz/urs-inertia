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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Illuminate\Routing\Controller;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Support\Str;
use App\Http\Traits\NotifiesOnHistoryCreation;
use Barryvdh\DomPDF\Facade\Pdf;

class AppRequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    use NotifiesOnHistoryCreation;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Menampilkan daftar permohonan.
     */
    public function index(Request $request)
    {
        // Helper untuk menerapkan filter pencarian dan status (agar bisa dipakai di query utama & laporan)
        $applyFilters = function ($query) use ($request) {
            // Filter Searching (Judul atau Nama Pemohon)
            if ($request->filled('search')) {
                $search = Str::lower($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where(DB::raw('LOWER(title)'), 'like', '%' . $search . '%')
                          ->orWhereHas('user', function ($subQ) use ($search) {
                              $subQ->where(DB::raw('LOWER(name)'), 'like', '%' . $search . '%');
                          });
                });
            }

            // Filter Status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
        };

        // Ambil data permohonan berdasarkan role
        $appRequestsQuery = AppRequest::with('user')->latest();

        if (!auth()->user()->hasRole('admin')) {
            // Jika bukan admin, tampilkan semua permohonan dari instansi yang sama.
            $appRequestsQuery->where('instansi', auth()->user()->instansi);
        }

        // Terapkan filter ke query utama
        $applyFilters($appRequestsQuery);

        $appRequests = $appRequestsQuery->paginate(10)->withQueryString();

        // Ambil semua data ringkas untuk keperluan checklist laporan (tanpa paginasi)
        $reportQuery = AppRequest::select('id', 'title', 'instansi', 'created_at', 'status')->latest();
        if (!auth()->user()->hasRole('admin')) {
            $reportQuery->where('instansi', auth()->user()->instansi);
        }

        // Terapkan filter yang sama ke query laporan
        $applyFilters($reportQuery);

        $allRequestsForReport = $reportQuery->get();

        // Render komponen Vue 'AppRequest/Index' dan kirimkan data sebagai props
        return Inertia::render('AppRequest/Index', [
            'appRequests' => $appRequests,
            'allRequestsForReport' => $allRequestsForReport,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Menampilkan form untuk membuat permohonan baru.
     */
    public function create()
    {
        // Render komponen Vue 'AppRequest/Create'
        return Inertia::render('AppRequest/Create', [
            'instansi' => collect(Instansi::cases())->map(fn($instansi) => [
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
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'file_pdf' => 'required|file|mimes:pdf|max:2048',
        ];

        // Jika admin, instansi wajib diisi dari form.
        if ($isAdmin) {
            $rules['instansi'] = ['required', new Enum(Instansi::class)];
        }

        $validatedData = $request->validate($rules);

        // Jika bukan admin, ambil instansi dari data user, abaikan input form.
        $instansi = $isAdmin ? $validatedData['instansi'] : $user->instansi;

        try {
            // Upload menggunakan Library Native (bukan Facade Laravel)
            $upload = (new UploadApi())->upload($request->file('file_pdf')->getRealPath(), [
                'folder' => 'pdfs',
                'resource_type' => 'raw',
            ]);

            // Ambil URL (Gunakan kurung siku [] karena hasilnya Array)
            $path = $upload['secure_url'];
        } catch (\Exception $e) {
            return back()->withErrors(['file_pdf' => 'Gagal Upload: ' . $e->getMessage()])->withInput();
        }
        // --- SELESAI CONFIG MANUAL ---

        $appRequest = AppRequest::create([
            'user_id' => $user->id,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'instansi' => $instansi,
            'file_path' => $path, // Path yang didapat dari manual upload
            'status' => RequestStatus::PERMOHONAN,
            'verification_status' => VerificationStatus::MENUNGGU,
        ]);

        $history = $appRequest->histories()->create([
            'user_id' => auth()->id(),
            'status' => RequestStatus::PERMOHONAN,
        ]);

        $this->sendNotificationForHistory($appRequest, $history);

        return redirect()->route('app-requests.index')->with('success', 'Permohonan berhasil diajukan!');
    }

    /**
     * Menampilkan detail permohonan.
     */
    public function show(AppRequest $appRequest)
    {
        if (auth()->user()->instansi !== $appRequest->instansi->value && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan untuk melihat permohonan ini.');
        }

        $appRequest->load([
            'user',
            'histories' => function ($query) {
                $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->with(['user', 'docSupports', 'imageSupports']);
            },
            'developmentActivities.subActivities'
        ]);

        // Untuk debugging: Cek apakah data aktivitas pengembangan berhasil dimuat.
        // dd($appRequest->developmentActivities);

        // Render komponen Vue 'AppRequest/Show'
        return Inertia::render('AppRequest/Show', [
            'appRequest' => $appRequest,
        ]);
    }

    /**
     * Memperbarui status permohonan yang sudah diverifikasi (oleh admin).
     */
    public function updateStatus(Request $request, AppRequest $appRequest)
    {
        // Otorisasi: Hanya admin yang bisa mengubah status.
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat mengubah status permohonan.');
        }

        // Otorisasi: Pastikan permohonan sudah diverifikasi
        if ($appRequest->verification_status !== VerificationStatus::DISETUJUI) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya permohonan yang disetujui yang dapat diubah status progresnya.'
                ], 403);
            }
            abort(403, 'Hanya permohonan yang disetujui yang dapat diubah status progresnya.');
        }

        $validated = $request->validate([
            'status' => ['required', new Enum(RequestStatus::class)],
            'end_date' => 'required|date|after_or_equal:today',
            'reason' => 'nullable|string',
        ]);

        DB::transaction(function () use ($appRequest, $validated, $request) {
            $newStatus = RequestStatus::from($validated['status']);

            // Siapkan data untuk diupdate
            $updateData = [
                'status' => $newStatus,
                'end_date' => $validated['end_date'],
            ];

            $appRequest->update($updateData);

            $history = $appRequest->histories()->create([
                'user_id' => auth()->id(),
                // Catat status progres di riwayat
                'status' => $newStatus,
                'reason' => $validated['reason'] ?? null,
            ]);

            $this->sendNotificationForHistory($appRequest, $history);
        });

        // Inertia akan secara otomatis menangani redirect ini dengan benar pada request XHR (AJAX).
        // Pesan 'success' akan dikirim sebagai flash message.
        return redirect()->route('app-requests.show', $appRequest)
            ->with('success', 'Status permohonan berhasil diubah!');
    }
    // ... yang kompatibel dengan cara kerja Inertia.
    // ... Pastikan Anda menyalin semua metode lainnya dari controller asli Anda ke sini.
    // ... Saya akan menyalin beberapa metode penting sebagai contoh.

    public function storeDocSupport(Request $request, AppRequest $appRequest, RequestHistory $history)
    {
        // Otorisasi: Hanya admin atau user dari instansi yang sama yang bisa menambahkan dokumen pendukung.
        if (!auth()->user()->hasRole('admin') && auth()->user()->instansi !== $appRequest->instansi->value) {
            abort(403, 'Anda tidak diizinkan menambahkan dokumen pendukung untuk permohonan ini.');
        }

        if ($history->app_request_id !== $appRequest->id) {
            abort(404);
        }

        // ... (Kode validasi sebelumnya tetap sama)
        $validated = $request->validate([
            'file_support_pdf' => 'required|file|mimes:pdf|max:2048',
        ]);

        $file = $request->file('file_support_pdf');


        // 2. Unggah menggunakan UploadApi (Bukan Facade Cloudinary)
        try {
            $upload = (new UploadApi())->upload($request->file('file_support_pdf')->getRealPath(), [
                'folder' => 'support_docs',
                'resource_type' => 'raw',
                'upload_preset' => 'urs-inertia' // Opsional, hapus jika error
            ]);

            // 3. Ambil data hasil upload
            // PERHATIAN: Hasilnya berupa ARRAY, bukan Object.
            // Jadi pakai kurung siku [], bukan tanda panah ->

            $path = $upload['secure_url']; // Pengganti getSecurePath()

        } catch (\Exception $e) {
            return back()->withErrors(['file_support_pdf' => 'Gagal upload ke Cloudinary: ' . $e->getMessage()]);
        }

        // --- SELESAI PERUBAHAN ---

        $history->docSupports()->create([
            'request_status' => $history->status,
            'file_path' => $path, // Sudah berisi URL string
            'file_name' => $file->getClientOriginalName(),
            'verification_status' => VerificationStatus::MENUNGGU->value, // Pastikan Enum sudah di-import
        ]);

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Dokumen pendukung berhasil ditambahkan!');
    }

    /**
     * Menyimpan bukti dukung gambar untuk sebuah riwayat proses.
     */
    public function storeImageSupport(Request $request, AppRequest $appRequest, RequestHistory $history)
    {
        // Otorisasi: Hanya admin atau user dari instansi yang sama yang bisa menambahkan bukti gambar.
        if (!auth()->user()->hasRole('admin') && auth()->user()->instansi !== $appRequest->instansi->value) {
            abort(403, 'Anda tidak diizinkan menambahkan bukti gambar untuk permohonan ini.');
        }

        if ($history->app_request_id !== $appRequest->id) {
            abort(404);
        }

        $validated = $request->validate([
            'file_support_image' => 'required|file|mimes:jpeg,jpg,png,gif|max:5120',
        ]);

        $file = $request->file('file_support_image');


        try {
            // Upload menggunakan Library Native (bukan Facade Laravel)
            $upload = (new UploadApi())->upload($request->file('file_support_image')->getRealPath(), [
                'folder' => 'images',
                'resource_type' => 'image',
                'upload_preset' => 'urs-inertia' // Opsional
            ]);

            // Ambil URL (Gunakan kurung siku [] karena hasilnya Array)
            $path = $upload['secure_url'];
        } catch (\Exception $e) {
            return back()->withErrors(['file_pdf' => 'Gagal Upload: ' . $e->getMessage()])->withInput();
        }


        $history->imageSupports()->create([
            // Simpan status progres dari riwayat terkait
            'request_status' => $history->status,
            'image_path' => $path,
            'image_name' => $file->getClientOriginalName(),
            'verification_status' => VerificationStatus::MENUNGGU->value,
        ]);

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Bukti dukung gambar berhasil ditambahkan!');
    }

    /**
     * Mengunduh file PDF utama.
     */
    public function download(AppRequest $appRequest)
    {
        if (auth()->user()->instansi !== $appRequest->instansi->value && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses file ini.');
        }
        if (!$appRequest->file_path) {
            abort(404, 'File tidak ditemukan.');
        }

        $url = $appRequest->file_path;

    // Cek safety: Jika URL kosong
    if (empty($url)) {
        abort(404, 'Link file tidak ditemukan di database.');
    }

 $response = Http::withoutVerifying()->get($url);

    if ($response->failed()) {
        // Jika error, kita tampilkan pesan debug biar tahu kenapa
        return response()->json([
            'message' => 'Gagal mengambil file dari Cloudinary',
            'status' => $response->status(),
            'target_url' => $url // Kita cek URL apa yang sebenarnya diakses
        ], 404);
    }


    // 4. Buat Nama File yang Bagus
    $filename = 'Dokumen_' . str_replace([' ', '/'], '_', $appRequest->title ?? 'download') . '.pdf';

    // 5. Kirim ke Browser User
    return response()->streamDownload(function () use ($response) {
        echo $response->body();
    }, $filename, [
        'Content-Type' => 'application/pdf',
    ]);
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

            $verificationHistory = $appRequest->histories()->create([
                'user_id' => auth()->id(),
                'status' => $verificationStatus->value,
                'type' => 'verification', // Tambahkan baris ini
                'reason' => $validated['reason'] ?? null,
            ]);

            $this->sendNotificationForHistory($appRequest, $verificationHistory);

            if ($verificationStatus === VerificationStatus::DISETUJUI) {
                $allStatuses = RequestStatus::cases();
                $currentIndex = array_search($appRequest->status, $allStatuses, true);
                if ($currentIndex !== false && isset($allStatuses[$currentIndex + 1])) {
                    $nextStatus = $allStatuses[$currentIndex + 1];
                    $appRequest->status = $nextStatus;
                    $statusHistory = $appRequest->histories()->create([
                        'user_id' => auth()->id(),
                        'status' => $nextStatus->value,
                        'reason' => 'Status otomatis naik setelah verifikasi disetujui.',
                    ]);
                    $this->sendNotificationForHistory($appRequest, $statusHistory);
                }
            }

            if ($verificationStatus === VerificationStatus::DITOLAK) {
                $appRequest->status = RequestStatus::PERMOHONAN;
            }

            $appRequest->save();
        });

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Status permohonan berhasil diperbarui!');
    }

    /**
     * Mengunduh file dokumen pendukung.
     */
    public function downloadDocSupport(RequestDocSupport $docSupport)
    {
      // 1. Otorisasi
        $appRequest = $docSupport->requestHistory->appRequest;

        if (auth()->user()->instansi !== $appRequest->instansi->value && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses file ini.');
        }

        // 2. Cek URL di Database
        $url = $docSupport->file_path;
        if (empty($url)) {
            abort(404, 'File tidak ditemukan.');
        }

        // 3. Ambil File dari Cloudinary (Server-to-Server)
        // 'withoutVerifying' penting untuk mengatasi masalah SSL di Localhost Windows
        $response = Http::withoutVerifying()->get($url);

        if ($response->failed()) {
            abort(404, 'Gagal mengambil file dari server penyimpanan.');
        }

        // 4. Tentukan Nama File
        // Kita gunakan nama asli file jika ada, atau buat nama baru yang rapi
        $originalName = $docSupport->file_name ?? 'Dokumen_Pendukung.pdf';
        // Bersihkan nama file dari karakter aneh
        $filename = Str::ascii($originalName);

        // 5. Stream ke Browser
        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $filename, [
            // Paksa Content-Type jadi PDF (karena ini khusus fungsi dokumen)
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Menampilkan gambar pendukung.
     */
    public function viewImageSupport(RequestImageSupport $imageSupport)
    {
        $appRequest = $imageSupport->requestHistory->appRequest;

        if (auth()->user()->instansi !== $appRequest->instansi->value && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses gambar ini.');
        }

        // 2. Cek URL
        $url = $imageSupport->image_path;
        if (empty($url)) {
            abort(404, 'Gambar tidak ditemukan.');
        }

        // 3. Ambil Gambar dari Cloudinary
        $response = Http::withoutVerifying()->get($url);

        if ($response->failed()) {
            abort(404, 'Gagal mengambil gambar dari server.');
        }

        // 4. Deteksi Content-Type (PENTING UNTUK GAMBAR)
        // Agar browser tahu apakah ini JPG, PNG, atau GIF
        $contentType = $response->header('Content-Type');

        // Buat nama file default jika tidak ada nama di database
        $nameFromDb = $imageSupport->image_name ?? 'bukti_dukung.jpg';
        $filename = Str::ascii($nameFromDb);

        // 5. Stream
        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $contentType, // Dinamis sesuai isi file
        ]);
    }

    /**
     * Memverifikasi dokumen pendukung (oleh admin).
     */
    public function verifyDocSupport(Request $request, RequestDocSupport $docSupport)
    {
        // Otorisasi: Hanya admin yang bisa melakukan verifikasi.
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'doc_verification_status' => ['required', new Enum(VerificationStatus::class)],
            'doc_reason' => 'nullable|string|required_if:doc_verification_status,' . VerificationStatus::DITOLAK->value,
        ]);

        $docSupport->update([
            'verification_status' => $validated['doc_verification_status'],
            'reason' => $validated['doc_reason'] ?? null,
        ]);

        // Redirect kembali ke halaman detail permohonan
        return redirect()->route('app-requests.show', $docSupport->requestHistory->appRequest)
            ->with('success', 'Status dokumen pendukung berhasil diperbarui!');
    }

    /**
     * Memverifikasi gambar pendukung (oleh admin).
     */
    public function verifyImageSupport(Request $request, RequestImageSupport $imageSupport)
    {
        // Otorisasi: Hanya admin yang bisa melakukan verifikasi.
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'image_verification_status' => ['required', new Enum(VerificationStatus::class)],
            'image_reason' => 'nullable|string|required_if:image_verification_status,' . VerificationStatus::DITOLAK->value,
        ]);

        $imageSupport->update([
            'verification_status' => $validated['image_verification_status'],
            'reason' => $validated['image_reason'] ?? null,
        ]);

        // Redirect kembali ke halaman detail permohonan
        return redirect()->route('app-requests.show', $imageSupport->requestHistory->appRequest)
            ->with('success', 'Status gambar pendukung berhasil diperbarui!');
    }

    /**
     * Membuat laporan PDF berdasarkan checklist.
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'request_ids' => 'required|array|min:1',
            'request_ids.*' => 'exists:app_requests,id',
        ]);

        $requests = AppRequest::with([
            'user',
            'histories.docSupports',
            'histories.imageSupports'
        ])
            ->whereIn('id', $request->request_ids)
            ->latest()
            ->get();

        // Otorisasi tambahan: filter jika bukan admin
        if (!auth()->user()->hasRole('admin')) {
            $requests = $requests->filter(fn($req) => $req->instansi->value === auth()->user()->instansi);
        }

        $pdf = Pdf::loadView('reports.app_requests', ['requests' => $requests])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => true]); // Penting agar gambar dari URL bisa muncul
        return $pdf->download('Laporan_Permohonan_' . now()->format('Ymd_His') . '.pdf');
    }
}
