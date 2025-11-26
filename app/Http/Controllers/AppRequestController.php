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
use Illuminate\Routing\Controller;
use Cloudinary\Api\Upload\UploadApi;

class AppRequestController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

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
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'instansi' => ['required', new Enum(Instansi::class)],
            'file_pdf' => 'required|file|mimes:pdf|max:2048',
        ]);


        try {
            // Upload menggunakan Library Native (bukan Facade Laravel)
            $upload = (new UploadApi())->upload($request->file('file_pdf')->getRealPath(), [
                'folder' => 'pdfs',
                'resource_type' => 'auto',
                'upload_preset' => 'urs-inertia' // Opsional
            ]);

            // Ambil URL (Gunakan kurung siku [] karena hasilnya Array)
            $path = $upload['secure_url'];
        } catch (\Exception $e) {
            return back()->withErrors(['file_pdf' => 'Gagal Upload: ' . $e->getMessage()])->withInput();
        }
        // --- SELESAI CONFIG MANUAL ---

        $appRequest = AppRequest::create([
            'user_id' => auth()->id(),
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'instansi' => $validatedData['instansi'],
            'file_path' => $path, // Path yang didapat dari manual upload
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

            $appRequest->histories()->create([
                'user_id' => auth()->id(),
                // Catat status progres di riwayat
                'status' => $newStatus,
                'reason' => $validated['reason'] ?? null,
            ]);
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
        // Otorisasi: Hanya admin atau pemilik permohonan yang bisa menambahkan dokumen pendukung.
        if (!auth()->user()->hasRole('admin') && auth()->id() !== $appRequest->user_id) {
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
                'resource_type' => 'auto',
                'upload_preset' => 'urs-inertia' // Opsional, hapus jika error
            ]);

            // 3. Ambil data hasil upload
            // PERHATIAN: Hasilnya berupa ARRAY, bukan Object.
            // Jadi pakai kurung siku [], bukan tanda panah ->

            $path = $upload['secure_url']; // Pengganti getSecurePath()
            $publicId = $upload['public_id']; // Pengganti getPublicId()

        } catch (\Exception $e) {
            return back()->withErrors(['file_support_pdf' => 'Gagal upload ke Cloudinary: ' . $e->getMessage()]);
        }

        // --- SELESAI PERUBAHAN ---

        $history->docSupports()->create([
            'request_status' => $history->status,
            'file_path' => $path, // Sudah berisi URL string
            'file_name' => $file->getClientOriginalName(),
            'verification_status' => VerificationStatus::MENUNGGU->value, // Pastikan Enum sudah di-import
            'public_id' => $publicId,
        ]);

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Dokumen pendukung berhasil ditambahkan!');
    }

    /**
     * Menyimpan bukti dukung gambar untuk sebuah riwayat proses.
     */
    public function storeImageSupport(Request $request, AppRequest $appRequest, RequestHistory $history)
    {
        // Otorisasi: Hanya admin atau pemilik permohonan yang bisa menambahkan bukti gambar.
        if (!auth()->user()->hasRole('admin') && auth()->id() !== $appRequest->user_id) {
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
                'resource_type' => 'auto',
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
            'public_id' => $publicId, // Simpan public_id
        ]);

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Bukti dukung gambar berhasil ditambahkan!');
    }

    /**
     * Mengunduh file PDF utama.
     */
    public function download(AppRequest $appRequest)
    {
        if (auth()->user()->id !== $appRequest->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses file ini.');
        }
        if (!$appRequest->file_path) {
            abort(404, 'File tidak ditemukan.');
        }

        $url = $appRequest->file_path;

        $downloadUrl = str_replace('/upload/', '/upload/fl_attachment/', $url);
        // Redirect ke URL Cloudinary untuk menampilkan file
        return redirect()->away($downloadUrl);
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
                'type' => 'verification', // Tambahkan baris ini
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

    /**
     * Mengunduh file dokumen pendukung.
     */
    public function downloadDocSupport(RequestDocSupport $docSupport)
    {
        // Otorisasi: Pastikan pengguna adalah pemilik permohonan atau admin.
        // Kita mengambil AppRequest melalui relasi: DocSupport -> History -> AppRequest
        $appRequest = $docSupport->requestHistory->appRequest;

        if (auth()->user()->id !== $appRequest->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses file ini.');
        }

        // Cek apakah path file ada di database
        if (!$docSupport->file_path) {
            abort(404, 'File tidak ditemukan.');
        }

        // Redirect ke URL Cloudinary untuk menampilkan file
        return redirect()->away($docSupport->file_path);
    }

    /**
     * Menampilkan gambar pendukung.
     */
    public function viewImageSupport(RequestImageSupport $imageSupport)
    {
        // Otorisasi: Pastikan pengguna adalah pemilik permohonan atau admin
        $appRequest = $imageSupport->requestHistory->appRequest;

        if (auth()->user()->id !== $appRequest->user_id && !auth()->user()->hasRole('admin')) {
            abort(403, 'Anda tidak diizinkan mengakses gambar ini.');
        }

        // Cek apakah path gambar ada di database
        if (!$imageSupport->image_path) {
            abort(404, 'Gambar tidak ditemukan.');
        }

        // Redirect ke URL Cloudinary untuk menampilkan gambar
        return redirect()->away($imageSupport->image_path);
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
}
