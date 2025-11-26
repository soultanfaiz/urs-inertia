<?php

namespace App\Http\Controllers;

use App\Models\SubDevelopmentActivity;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;

class SubDevelopmentActivityController extends Controller
{

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

        // Redirect back to the previous page. Inertia will handle the prop updates.
        return Redirect::back()->with('success', 'Status detail pekerjaan berhasil diperbarui.');
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

        // Redirect back. Inertia will update the page with the latest data.
        return Redirect::back()->with('success', 'Detail pekerjaan berhasil dihapus.');
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

        foreach ($validated['sub_activities'] as $subActivityName) {
            SubDevelopmentActivity::create([
                'development_activity_id' => $validated['development_activity_id'],
                'name' => $subActivityName,
                'is_completed' => false,
            ]);
        }

        // Update parent activity completion status
        $parentActivity = \App\Models\DevelopmentActivity::find($validated['development_activity_id']);
        $allSubActivitiesCompleted = $parentActivity->subActivities()->where('is_completed', false)->doesntExist();
        $hasSubActivities = $parentActivity->subActivities()->exists();

        $parentActivity->update([
            'is_completed' => $hasSubActivities && $allSubActivitiesCompleted
        ]);

        // Redirect back. Inertia will update the page with the latest data.
        return Redirect::back()->with('success', 'Detail pekerjaan berhasil ditambahkan.');
    }
}
