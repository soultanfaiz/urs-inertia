<?php

namespace App\Http\Controllers;

use App\Models\SubDevelopmentActivity;
use Illuminate\Http\Request;

class SubDevelopmentActivityController extends Controller
{
    /**
     * Terapkan middleware otorisasi untuk memastikan hanya admin yang bisa mengakses.
     */
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    /**
     * Toggle the completion status of a sub-development activity.
     */
    public function toggleStatus(Request $request, SubDevelopmentActivity $subActivity)
    {
        // Otorisasi bisa ditambahkan di sini jika diperlukan,
        // namun route sudah dilindungi oleh middleware 'role:admin'

        // Toggle status is_completed
        $subActivity->update([
            'is_completed' => !$subActivity->is_completed,
        ]);

        // Cek apakah semua sub-aktivitas lain dalam aktivitas induk yang sama sudah selesai
        $parentActivity = $subActivity->developmentActivity;
        $allSubActivitiesCompleted = $parentActivity->subActivities()->where('is_completed', false)->doesntExist();

        // Jika semua sub-aktivitas selesai, tandai aktivitas induk sebagai selesai.
        // Jika tidak, pastikan aktivitas induk tidak ditandai sebagai selesai.
        if ($allSubActivitiesCompleted) {
            $parentActivity->update(['is_completed' => true]);
        } else {
            $parentActivity->update(['is_completed' => false]);
        }

        // Dengan Inertia, semua request dari frontend adalah XHR, jadi kita selalu mengembalikan JSON.
        return response()->json([
            'success' => true,
            'message' => 'Status detail pekerjaan berhasil diperbarui.',
            'parent_activity_completed' => $parentActivity->is_completed,
        ]);
    }

    /**
     * Remove the specified sub-development activity from storage.
     */
    public function destroy(Request $request, SubDevelopmentActivity $subActivity)
    {
        // Otorisasi
        // $this->authorize('delete', $subActivity);

        $parentActivity = $subActivity->developmentActivity;
        $parentActivityId = $parentActivity->id;

        $subActivity->delete();

        // Cek kembali status parent activity setelah menghapus
        $allSubActivitiesCompleted = $parentActivity->subActivities()->where('is_completed', false)->doesntExist();
        $hasSubActivities = $parentActivity->subActivities()->exists();

        // Jika semua sub-aktivitas selesai (atau tidak ada sub-aktivitas lagi), tandai induk sebagai selesai.
        // Jika tidak, pastikan induk tidak selesai.
        $parentActivity->update([
            'is_completed' => $hasSubActivities && $allSubActivitiesCompleted
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail pekerjaan berhasil dihapus.',
            // Kirim status terbaru dari parent activity untuk memudahkan update di frontend
            'parent_activity_id' => $parentActivityId,
            'parent_activity_completed' => $parentActivity->is_completed,
        ]);
    }

    /**
     * Store a newly created sub-development activity.
     */
    public function store(Request $request)
    {
        // Validasi request
        $validated = $request->validate([
            'sub_activities' => 'required|array|min:1',
            'sub_activities.*' => 'required|string|max:255',
            'development_activity_id' => 'required|exists:development_activities,id',
        ]);

        $createdSubActivities = [];

        foreach ($validated['sub_activities'] as $subActivityName) {
            $subActivity = SubDevelopmentActivity::create([
                'development_activity_id' => $validated['development_activity_id'],
                'name' => $subActivityName,
                'is_completed' => false,
            ]);

            $createdSubActivities[] = [
                'id' => $subActivity->id,
                'name' => $subActivity->name,
                'is_completed' => $subActivity->is_completed,
                'created_at' => $subActivity->created_at->toDateTimeString(),
            ];
        }

        // Update parent activity completion status
        $parentActivity = \App\Models\DevelopmentActivity::find($validated['development_activity_id']);
        $allSubActivitiesCompleted = $parentActivity->subActivities()->where('is_completed', false)->doesntExist();
        $hasSubActivities = $parentActivity->subActivities()->exists();

        $parentActivity->update([
            'is_completed' => $hasSubActivities && $allSubActivitiesCompleted
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail pekerjaan berhasil ditambahkan.',
            'sub_activities' => $createdSubActivities,
            // Kirim juga status terbaru dari parent activity
            'parent_activity_id' => $validated['development_activity_id'],
            'parent_activity_completed' => $parentActivity->is_completed,
        ]);
    }
}
