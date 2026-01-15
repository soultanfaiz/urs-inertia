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

        // Default metadata
        $metadata = [
            'leader' => '-',
            'speakers' => '-',
            'place' => '-',
            'participants' => '-',
        ];

        // Attempt to fill metadata using AI if notes exist
        if ($notes->isNotEmpty()) {
            try {
                $notesText = $notes->map(function ($note) {
                    return "Judul: {$note->title}\nIsi: " . strip_tags($note->note);
                })->join("\n\n");

                $systemPrompt = "Anda adalah asisten administratif. Tugas Anda adalah mengekstrak informasi detail dari notulen rapat.";
                $userPrompt = "Analisis catatan rapat berikut dan ekstrak informasi metadata secara akurat.\n\n" .
                    "Catatan:\n{$notesText}\n\n" .
                    "Instruksi:\n" .
                    "Ekstrak data berikut dalam format JSON MURNI (tanpa markdown ```json):\n" .
                    "- leader: Nama Pimpinan Rapat (jika tidak ditemukan return '-')\n" .
                    "- speakers: Nama Narasumber (jika tidak ditemukan return '-')\n" .
                    "- place: Tempat Pelaksanaan (jika tidak ditemukan return '-')\n" .
                    "- participants: Daftar Peserta/Hadirin (string dipisahkan koma, jika tidak ditemukan return '-')\n\n" .
                    "Contoh JSON:\n" .
                    "{\"leader\": \"Budi\", \"speakers\": \"Dr. Siti\", \"place\": \"Ruang Rapat 1\", \"participants\": \"Andi, Joko, Rina\"}";

                // Limit text length to avoid token limits if necessary (approx 10k chars)
                $userPrompt = substr($userPrompt, 0, 12000);

                $aiResult = $this->openRouter->generate($systemPrompt, $userPrompt);

                if ($aiResult['success']) {
                    $jsonStr = $aiResult['data'];
                    // Remove code blocks if present
                    if (strpos($jsonStr, '```') !== false) {
                        $jsonStr = preg_replace('/^```json\s*|\s*```$/s', '', $jsonStr);
                        // Also handle simple code blocks without language
                        $jsonStr = preg_replace('/^```\s*|\s*```$/s', '', $jsonStr);
                    }

                    $data = json_decode(trim($jsonStr), true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                        $metadata = array_merge($metadata, array_intersect_key($data, $metadata));
                    }
                }
            } catch (\Exception $e) {
                // Silent fail, just use defaults
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
