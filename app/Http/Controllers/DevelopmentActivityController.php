<?php

namespace App\Http\Controllers;

use App\Models\AppRequest;
use App\Models\DevelopmentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Inertia\Inertia;



class DevelopmentActivityController extends Controller
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
     * Menyimpan aktivitas pengembangan baru.
     */
    public function store(Request $request, AppRequest $appRequest)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'sub_activities' => 'nullable|array',
            'sub_activities.*' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pic' => 'nullable|array', // Validasi array
            // 'pic.*' => 'exists:pics,id', // Hapus atau ganti supaya bisa string bebas (OPD name)
            'pic.*' => 'string', // Allow string IDs or Names
        ]);

        $activity = DB::transaction(function () use ($appRequest, $validated) {
            // Dapatkan iterasi terakhir dan tambahkan 1
            $maxIteration = $appRequest->developmentActivities()->max('iteration_count') ?? 0;
            $newIteration = $maxIteration + 1;

            // 1. Buat aktivitas utama
            $activityData = [
                'description' => $validated['description'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'iteration_count' => $newIteration,
                'pic' => $validated['pic'] ?? [], // Simpan array PIC
            ];

            $activity = $appRequest->developmentActivities()->create($activityData);

            // 2. Buat sub-aktivitas
            $subActivitiesData = [];
            $subActivitiesInput = $validated['sub_activities'] ?? [];
            if (is_array($subActivitiesInput)) {
                foreach ($subActivitiesInput as $subActivityName) {
                    if (!empty($subActivityName)) { // Pastikan sub-aktivitas kosong tidak ditambahkan
                        $subActivitiesData[] = ['name' => $subActivityName];
                    }
                }
            }
            if (!empty($subActivitiesData)) {
                $activity->subActivities()->createMany($subActivitiesData);
            }

            // Muat kembali relasi subActivities untuk dikirim sebagai respons
            return $activity->load('subActivities');
        });

        if ($request->boolean('return_to_index')) {
            return redirect()->back()->with('success', 'Aktivitas pengembangan berhasil ditambahkan.');
        }

        return redirect()->route('app-requests.show', $appRequest)->with('success', 'Aktivitas pengembangan berhasil ditambahkan.');
    }

    /**
     * Memperbarui aktivitas pengembangan yang ada.
     */
    public function update(Request $request, DevelopmentActivity $developmentActivity)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        // Unified validation for both Contexts (Axios/JSON and Inertia/Form)
        $validated = $request->validate([
            'description' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pic' => 'nullable|array',
            'pic.*' => 'string',
            'is_completed' => 'nullable|boolean', // Optional, mainly for switching status
        ]);

        // Prepare update data
        $updateData = [
            'description' => $validated['description'],
        ];

        // Handle PIC if present
        if (array_key_exists('pic', $validated)) {
            $updateData['pic'] = $validated['pic'] ?? [];
        }

        // Handle Dates (ensure they can be set to null)
        if (array_key_exists('start_date', $validated)) {
            $updateData['start_date'] = !empty($validated['start_date']) ? $validated['start_date'] : null;
        }
        if (array_key_exists('end_date', $validated)) {
            $updateData['end_date'] = !empty($validated['end_date']) ? $validated['end_date'] : null;
        }

        // Handle Completion Status
        if (isset($validated['is_completed'])) {
            $updateData['is_completed'] = $validated['is_completed'];
        }

        $developmentActivity->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aktivitas pengembangan berhasil diperbarui.'
            ]);
        }

        return redirect()->back()->with('success', 'Aktivitas pengembangan berhasil diperbarui.');
    }

    /**
     * Menghapus aktivitas pengembangan.
     */
    public function destroy(Request $request, DevelopmentActivity $developmentActivity)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $appRequestId = $developmentActivity->app_request_id;
        $developmentActivity->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aktivitas pengembangan berhasil dihapus.'
            ]);
        }

        return redirect()->route('app-requests.show', $appRequestId)->with('success', 'Aktivitas pengembangan berhasil dihapus.');
    }

    /**
     * Mengurutkan ulang aktivitas pengembangan.
     * Menggunakan DB transaction untuk mencegah race condition.
     */
    public function reorder(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        try {
            $validated = $request->validate([
                'ordered_ids' => 'required|array',
                'ordered_ids.*' => 'required|integer|exists:development_activities,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($validated) {
                // Lock table untuk mencegah concurrent updates
                DB::table('development_activities')
                    ->whereIn('id', $validated['ordered_ids'])
                    ->lockForUpdate()
                    ->get();

                // Update semua iteration_count dalam satu transaction
                foreach ($validated['ordered_ids'] as $index => $activityId) {
                    DevelopmentActivity::where('id', $activityId)->update([
                        'iteration_count' => $index + 1
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Urutan aktivitas berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui urutan. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Memperbarui status aktivitas pengembangan.
     */
    public function updateStatus(Request $request, DevelopmentActivity $developmentActivity)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'is_completed' => 'required|boolean',
        ]);

        $developmentActivity->update([
            'is_completed' => $validated['is_completed']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status aktivitas berhasil diperbarui.'
        ]);
    }

    /**
     * Menambahkan sub-aktivitas baru ke aktivitas pengembangan yang ada.
     */
    public function addSubActivities(Request $request, DevelopmentActivity $developmentActivity)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }
        // Gunakan try-catch untuk menangani ValidationException secara manual
        try {
            $validated = $request->validate([
                'sub_activities' => 'required|array|min:1',
                'sub_activities.*' => 'required|string|max:255',
            ]);

            $newSubActivities = DB::transaction(function () use ($developmentActivity, $validated) {
                $subActivities = [];
                foreach ($validated['sub_activities'] as $subActivityName) {
                    if (!empty($subActivityName)) {
                        $subActivities[] = $developmentActivity->subActivities()->create([
                            'name' => $subActivityName,
                        ]);
                    }
                }
                return $subActivities;
            });

            // Jika berhasil, pastikan status parent activity adalah 'belum selesai'
            if ($developmentActivity->is_completed) {
                $developmentActivity->update(['is_completed' => false]);
            }

            // Muat ulang aktivitas dengan sub-aktivitasnya untuk mendapatkan data terbaru.
            $updatedActivity = $developmentActivity->load('subActivities');

            return response()->json([
                'success' => true,
                'message' => 'Detail pekerjaan berhasil ditambahkan.',
                'activity' => $updatedActivity, // Kirim seluruh objek aktivitas yang sudah diupdate
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Jika validasi gagal, kirim respons JSON dengan pesan error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan validasi.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity adalah status yang tepat untuk error validasi
        }
    }

    /**
     * Merender satu item aktivitas pengembangan sebagai HTML.
     * Digunakan untuk me-refresh komponen via AJAX.
     */
    public function renderItem(DevelopmentActivity $activity)
    {
        // Otorisasi bisa ditambahkan di sini jika perlu
        // $this->authorize('view', $activity->appRequest);

        // Dengan Inertia/Vue, kirim kembali data aktivitas yang sudah di-load relasinya.
        // Frontend akan menggunakan data ini untuk me-refresh komponen.
        return response()->json(['activity' => $activity->load('subActivities')]);
    }
    /**
     * Menampilkan daftar semua aktivitas pengembangan yang belum selesai.
     */
    public function index()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini.');
        }

        $activities = DevelopmentActivity::with('appRequest')
            ->where('is_completed', false)
            ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END, end_date ASC')
            ->get();

        // Ambil semua PIC
        $allPics = \App\Models\Pic::all();

        // Kumpulkan semua ID PIC yang sedang mengerjakan tugas (aktivitas belum selesai)
        $busyPicIds = collect();
        foreach ($activities as $activity) {
            if (!empty($activity->pic) && is_array($activity->pic)) {
                $busyPicIds = $busyPicIds->merge($activity->pic);
            }
        }
        $busyPicIds = $busyPicIds->unique()->values()->all();

        // Filter PIC yang tidak sedang mengerjakan tugas
        $availablePics = $allPics->filter(function ($pic) use ($busyPicIds) {
            return !in_array($pic->id, $busyPicIds);
        })->values();

        return Inertia::render('DevelopmentActivity/Index', [
            'activities' => $activities,
            'availablePics' => $availablePics,
            'pics' => $allPics,
            'appRequests' => AppRequest::where('status', '!=', \App\Enums\RequestStatus::SELESAI)
                ->where('verification_status', '!=', \App\Enums\VerificationStatus::DITOLAK)
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    /**
     * Menugaskan PIC ke aktivitas pengembangan.
     */
    public function assignPic(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat melakukan verifikasi.');
        }

        $validated = $request->validate([
            'activity_id' => 'required|exists:development_activities,id',
            'pic_id' => 'required|exists:pics,id',
        ]);

        $activity = DevelopmentActivity::findOrFail($validated['activity_id']);

        $currentPics = $activity->pic ?? [];
        if (!in_array($validated['pic_id'], $currentPics)) {
            $currentPics[] = $validated['pic_id'];
            $activity->pic = $currentPics;
            $activity->save();
        }

        return redirect()->back()->with('success', 'PIC berhasil ditugaskan.');
    }
}
