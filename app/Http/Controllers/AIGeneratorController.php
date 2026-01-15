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
        // If existing note is provided, enhance/rewrite it
        if (!empty(trim($existingNote))) {
            $prompt = "Berdasarkan catatan berikut, buatkan notulen rapat yang lebih lengkap dan terstruktur.\n\n";
            $prompt .= "Judul: \"{$title}\"\n\n";
            $prompt .= "Catatan yang sudah ada:\n";
            $prompt .= "---\n{$existingNote}\n---\n\n";
            $prompt .= "Konteks Permohonan:\n";
            $prompt .= "- Judul Permohonan: {$appRequest->title}\n";
            $prompt .= "- Instansi: {$appRequest->instansi->value}\n";

            if (!empty($context)) {
                $prompt .= "\nInformasi Tambahan:\n{$context}\n";
            }

            $prompt .= "\nTugas:\n";
            $prompt .= "1. Perbaiki dan lengkapi catatan di atas menjadi notulen yang profesional\n";
            $prompt .= "2. Pertahankan informasi penting dari catatan asli\n";
            $prompt .= "3. Tambahkan struktur yang jelas (Pembukaan, Pembahasan, Kesimpulan, Tindak Lanjut)\n";
            $prompt .= "4. Gunakan bahasa Indonesia yang formal dan profesional\n";

            return $prompt;
        }

        // Generate new note from scratch
        $prompt = "Buatkan notulen atau catatan rapat dengan judul: \"{$title}\"\n\n";
        $prompt .= "Konteks Permohonan:\n";
        $prompt .= "- Judul Permohonan: {$appRequest->title}\n";
        $prompt .= "- Deskripsi: {$appRequest->description}\n";
        $prompt .= "- Instansi: {$appRequest->instansi->value}\n";

        if ($appRequest->start_date) {
            $prompt .= "- Tanggal Mulai: " . $appRequest->start_date->translatedFormat('d F Y') . "\n";
        }

        if (!empty($context)) {
            $prompt .= "\nInformasi Tambahan:\n{$context}\n";
        }

        $prompt .= "\nBuatkan notulen yang mencakup:\n";
        $prompt .= "1. Pembukaan\n";
        $prompt .= "2. Pembahasan dan Diskusi\n";
        $prompt .= "3. Kesimpulan/Hasil\n";
        $prompt .= "4. Tindak Lanjut (jika relevan)\n";
        $prompt .= "\nGunakan format yang jelas dan profesional. Gunakan bullet points atau numbered lists jika diperlukan.";

        return $prompt;
    }
}
