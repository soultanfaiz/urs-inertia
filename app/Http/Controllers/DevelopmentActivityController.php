<?php

namespace App\Http\Controllers;

use App\Models\AppRequest;
use App\Models\DevelopmentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;



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
            'sub_activities' => 'required|array|min:1',
            'sub_activities.*' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
            ];

            $activity = $appRequest->developmentActivities()->create($activityData);

            // 2. Buat sub-aktivitas
            $subActivitiesData = [];
            foreach ($validated['sub_activities'] as $subActivityName) {
                if (!empty($subActivityName)) { // Pastikan sub-aktivitas kosong tidak ditambahkan
                    $subActivitiesData[] = ['name' => $subActivityName];
                }
            }
            $activity->subActivities()->createMany($subActivitiesData);

            // Muat kembali relasi subActivities untuk dikirim sebagai respons
            return $activity->load('subActivities');
        });

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

        if ($request->wantsJson()) {
            // Handle AJAX request for updating description and dates
            $validated = $request->validate([
                'description' => 'required|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            // Prepare update data
            $updateData = [
                'description' => $validated['description'],
            ];

            // Add dates (handle empty string as null)
            if (isset($validated['start_date'])) {
                $updateData['start_date'] = !empty($validated['start_date']) ? $validated['start_date'] : null;
            }
            if (isset($validated['end_date'])) {
                $updateData['end_date'] = !empty($validated['end_date']) ? $validated['end_date'] : null;
            }

            $developmentActivity->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Aktivitas pengembangan berhasil diperbarui.'
            ]);
        }

        // Handle regular form request
        $validated = $request->validate([
            'description' => 'required|string',
            'is_completed' => 'required|boolean',
        ]);

        $developmentActivity->update($validated);

        return redirect()->route('app-requests.show', $developmentActivity->app_request_id)->with('success', 'Aktivitas pengembangan berhasil diperbarui.');
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
}
