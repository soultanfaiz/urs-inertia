<?php

namespace App\Http\Controllers;

use App\Models\AppRequest;
use App\Models\SupportingNote;
use Illuminate\Http\Request;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Routing\Controller;
use Barryvdh\DomPDF\Facade\Pdf;


class SupportingNoteController extends Controller
{
    protected $openRouter;

    public function __construct(\App\Services\OpenRouterService $openRouter)
    {
        $this->middleware('auth');
        $this->openRouter = $openRouter;
    }

    public function store(Request $request, AppRequest $appRequest)
    {
        // Otorisasi: Hanya admin yang bisa menambah catatan
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat menambahkan catatan.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'note' => 'required|string',
            'generated_note' => 'nullable|string',
            'image' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            try {
                $upload = (new UploadApi())->upload($request->file('image')->getRealPath(), [
                    'folder' => 'note_images',
                    'resource_type' => 'image',
                    'upload_preset' => 'urs-inertia'
                ]);
                $imagePath = $upload['secure_url'];
            } catch (\Exception $e) {
                return back()->withErrors(['image' => 'Gagal Upload Gambar: ' . $e->getMessage()])->withInput();
            }
        }

        $appRequest->supportingNotes()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'note' => $validated['note'],
            'generated_note' => $validated['generated_note'] ?? null,
            'image_path' => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function update(Request $request, SupportingNote $supportingNote)
    {
        // Otorisasi: Hanya admin yang bisa mengedit catatan
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat mengedit catatan.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'note' => 'required|string',
            'generated_note' => 'nullable|string',
            'image' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $imagePath = $supportingNote->image_path;

        if ($request->hasFile('image')) {
            try {
                $upload = (new UploadApi())->upload($request->file('image')->getRealPath(), [
                    'folder' => 'note_images',
                    'resource_type' => 'image',
                    'upload_preset' => 'urs-inertia'
                ]);
                $imagePath = $upload['secure_url'];
            } catch (\Exception $e) {
                return back()->withErrors(['image' => 'Gagal Upload Gambar: ' . $e->getMessage()])->withInput();
            }
        }

        $supportingNote->update([
            'title' => $validated['title'],
            'note' => $validated['note'],
            'generated_note' => $validated['generated_note'] ?? null,
            'image_path' => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Catatan berhasil diperbarui.');
    }

    /**
     * Menghapus catatan pendukung.
     */
    public function destroy(SupportingNote $supportingNote)
    {
        // Otorisasi: Hanya admin yang bisa menghapus catatan
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya admin yang dapat menghapus catatan.');
        }

        $supportingNote->delete();

        return redirect()->back()->with('success', 'Catatan berhasil dihapus.');
    }

    /**
     * Membuat laporan PDF untuk catatan pendukung.
     */
    public function generateReport(AppRequest $appRequest)
    {
        $user = auth()->user();

        // Otorisasi: Admin atau user dari instansi yang sama
        if (!$user->hasRole('admin') && $user->instansi !== $appRequest->instansi->value) {
            abort(403, 'Anda tidak diizinkan mengakses laporan ini.');
        }

        $appRequest->load(['user', 'supportingNotes.user']);
        $notes = $appRequest->supportingNotes->sortByDesc('created_at');

        // Default metadata (strings kosong agar tidak muncul di laporan jika kosong)
        $metadata = [
            'leader' => '',
            'speakers' => '',
            'place' => '',
            'participants' => '',
            'time' => '',
        ];

        // Attempt to fill metadata using AI if notes exist
        if ($notes->isNotEmpty()) {
            $extracted = app(AIGeneratorController::class)->extractMetadataFromNotes($notes);
            // Filter null values
            $extracted = array_filter($extracted, function ($value) {
                return !is_null($value) && $value !== '-';
            });
            $metadata = array_merge($metadata, $extracted);
        }

        // Determine Final Time Display
        $finalTime = '';
        if (!empty($metadata['time'])) {
            $finalTime = $metadata['time'] . ' WITA';
        } elseif ($appRequest->start_date) {
            $timeStr = $appRequest->start_date->format('H:i');
            if ($timeStr !== '00:00') {
                $finalTime = $timeStr . ' WITA s/d Selesai';
            }
        }
        $metadata['time_display'] = $finalTime;

        // Process notes to replace placeholders with dynamic data
        $replacements = [
            '[HARI_TANGGAL]' => $appRequest->start_date ? $appRequest->start_date->translatedFormat('l, d F Y') : '-',
            '[WAKTU]' => $metadata['time_display'] ?: '-', // Fallback to dash or keep empty? User said "kosongkan saja" for table, but usually placeholders in text might need content. Assuming empty is fine if "kosongkan saja" applies generally.
            '[TEMPAT]' => !empty($metadata['place']) ? $metadata['place'] : ($appRequest->place ?? ''),
            '[ACARA]' => $appRequest->title,
            '[PIMPINAN]' => !empty($metadata['leader']) ? $metadata['leader'] : '',
        ];

        // We iterate and modify the note content purely for display in the PDF
        foreach ($notes as $note) {
            foreach ($replacements as $placeholder => $value) {
                if (strpos($note->note, $placeholder) !== false) {
                    $note->note = str_replace($placeholder, $value, $note->note);
                }
            }
        }

        $pdf = Pdf::loadView('reports.notulen_kegiatan', [
            'appRequest' => $appRequest,
            'notes' => $notes,
            'metadata' => (object) $metadata, // Pass as object for generic access
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'chroot' => public_path(),
            ]);

        return $pdf->stream('Catatan_Pendukung_' . str_replace([' ', '/'], '_', $appRequest->title) . '_' . now()->format('Ymd_His') . '.pdf');
    }
}
