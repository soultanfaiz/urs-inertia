<?php

namespace App\Http\Controllers;

use App\Models\AppRequest;
use App\Models\SupportingNote;
use Illuminate\Http\Request;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Routing\Controller;

class SupportingNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
            'image_path' => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Catatan berhasil ditambahkan.');
    }
}
