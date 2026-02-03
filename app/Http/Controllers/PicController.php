<?php

namespace App\Http\Controllers;

use App\Models\Pic;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Management/Pic/Index', [
            'pics' => Pic::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        Pic::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pic $pic)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
        ]);

        $pic->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pic $pic)
    {
        $pic->delete();

        return redirect()->back();
    }
}
