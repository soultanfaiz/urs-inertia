<?php

namespace App\Http\Controllers;

use App\Models\AppRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class AIGeneratorController extends Controller
{
    protected $openRouter;

    public function __construct(\App\Services\OpenRouterService $openRouter)
    {
        $this->middleware('auth');
        $this->openRouter = $openRouter;
    }

    /**
     * Generate note text using AI via OpenRouter API.
     */
    public function generateNote(Request $request, AppRequest $appRequest)
    {
        $user = auth()->user();

        // Authorization: Admin or user from same instansi
        if (!$user->hasRole('admin') && $user->instansi !== $appRequest->instansi->value) {
            abort(403, 'Anda tidak diizinkan mengakses fitur ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'context' => 'nullable|string',
            'existing_note' => 'nullable|string',
        ]);

        // Build prompt for generating meeting notes
        $existingNote = $validated['existing_note'] ?? '';
        // Strip HTML tags from existing note for cleaner AI input
        $existingNoteText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $existingNote));
        $prompt = $this->buildPrompt($appRequest, $validated['title'], $validated['context'] ?? '', $existingNoteText);

        $systemPrompt = 'Anda adalah asisten yang membantu membuat notulen rapat dan catatan pendukung dalam bahasa Indonesia yang profesional dan terstruktur.';

        $result = $this->openRouter->generate($systemPrompt, $prompt);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'generated_note' => $result['data'],
                'model_used' => $result['model'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 500);
    }

    /**
     * Build prompt for AI based on app request context.
     */
    private function buildPrompt(AppRequest $appRequest, string $title, string $context, string $existingNote = ''): string
    {
        // Strict formatting instructions matching notulen_kegiatan.blade.php styles
        $formatInstructions = "INSTRUKSI FORMATTING (SANGAT PENTING):\n";
        $formatInstructions .= "1. JANGAN MENAMBAH INFORMASI BARU. Hanya format ulang teks yang diberikan.\n";
        $formatInstructions .= "2. Gunakan tag HTML berikut agar sesuai dengan tampilan laporan:\n";
        $formatInstructions .= "   - <h4>Judul Bagian</h4> untuk sub-judul\n";
        $formatInstructions .= "   - <p>Paragraf teks...</p> untuk isi teks\n";
        $formatInstructions .= "   - <ul><li>Poin list...</li></ul> untuk daftar poin\n";
        $formatInstructions .= "   - <ol><li>Urutan langkah...</li></ol> untuk daftar urutan\n";
        $formatInstructions .= "3. Jangan gunakan tag <h1>, <h2>, atau <h3>.\n";
        $formatInstructions .= "4. Gunakan 'Placeholder' berikut untuk data dinamis (JANGAN UBAH):\n";
        $formatInstructions .= "   - [HARI_TANGGAL] : untuk hari dan tanggal kegiatan\n";
        $formatInstructions .= "   - [WAKTU] : untuk jam pelaksanaan\n";
        $formatInstructions .= "   - [TEMPAT] : untuk lokasi rapat\n";
        $formatInstructions .= "   - [ACARA] : untuk judul acara/kegiatan\n";
        $formatInstructions .= "   - [PIMPINAN] : untuk nama pimpinan rapat\n";
        $formatInstructions .= "5. Rapikan tata bahasa dan ejaan tanpa mengubah makna.\n";

        // If existing note is provided, just format it
        if (!empty(trim($existingNote))) {
            $prompt = "Tugas: Format ulang catatan berikut agar rapi dan profesional untuk laporan notulen.\n\n";
            $prompt .= $formatInstructions . "\n\n";
            $prompt .= "TEKS UNTUK DIFORMAT:\n";
            $prompt .= "---\n{$existingNote}\n---\n";

            if (!empty($context)) {
                $prompt .= "\n(Gunakan konteks ini HANYA jika teks asli tidak lengkap/ambigu: {$context})\n";
            }

            return $prompt;
        }

        // Generate new note from scratch (Drafting phase)
        // Even for new notes, we want it concise and structured
        $prompt = "Buatkan kerangka notulen rapat yang singkat dan padat untuk:\n";
        $prompt .= "Judul: \"{$title}\"\n\n";
        $prompt .= "Konteks:\n";
        $prompt .= "- Permohonan: {$appRequest->title}\n";
        $prompt .= "- Deskripsi: {$appRequest->description}\n";

        if (!empty($context)) {
            $prompt .= "- Info Tambahan: {$context}\n";
        }

        $prompt .= "\n" . $formatInstructions;
        $prompt .= "\nBuatkan poin-poin pembahasan utama saja. Jangan bertele-tele.";

        return $prompt;
    }

    /**
     * Extract metadata from notes for report generation.
     *
     * @param \Illuminate\Support\Collection $notes
     * @return array
     */
    public function extractMetadataFromNotes($notes)
    {
        if ($notes->isEmpty()) {
            return [];
        }

        $notesText = $notes->map(function ($note) {
            return "Judul: {$note->title}\nIsi: " . strip_tags($note->note);
        })->join("\n\n");

        $systemPrompt = "Anda adalah asisten administratif. Tugas Anda adalah mengekstrak informasi detail dari notulen rapat.";
        $userPrompt = "Analisis catatan rapat berikut dan ekstrak informasi metadata secara akurat.\n\n" .
            "Catatan:\n{$notesText}\n\n" .
            "Instruksi:\n" .
            "Ekstrak data berikut dalam format JSON MURNI (tanpa markdown ```json):\n" .
            "- leader: Nama Pimpinan Rapat (jika tidak ditemukan return null)\n" .
            "- speakers: Nama Narasumber (jika tidak ditemukan return null)\n" .
            "- place: Tempat Pelaksanaan (jika tidak ditemukan return null)\n" .
            "- time: Waktu Pelaksanaan (format HH:mm, jika tidak ditemukan return null)\n" .
            "- participants: Daftar Peserta/Hadirin (string dipisahkan koma, jika tidak ditemukan return null)\n\n" .
            "Contoh JSON:\n" .
            "{\"leader\": \"Budi\", \"speakers\": \"Dr. Siti\", \"place\": \"Ruang Rapat 1\", \"time\": \"14:00\", \"participants\": \"Andi, Joko, Rina\"}";

        // Limit text length to avoid token limits if necessary (approx 12k chars)
        $userPrompt = substr($userPrompt, 0, 12000);

        try {
            $aiResult = $this->openRouter->generate($systemPrompt, $userPrompt);

            if ($aiResult['success']) {
                $jsonStr = $aiResult['data'];
                // Remove code blocks if present
                if (strpos($jsonStr, '```') !== false) {
                    $jsonStr = preg_replace('/^```json\s*|\s*```$/s', '', $jsonStr);
                    $jsonStr = preg_replace('/^```\s*|\s*```$/s', '', $jsonStr);
                }

                $data = json_decode(trim($jsonStr), true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // Log error or just return empty
        }

        return [];
    }
}
