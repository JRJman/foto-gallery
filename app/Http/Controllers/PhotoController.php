<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PhotoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'photos' => $this->photos(),
        ]);
    }

    public function manage(): Response
    {
        return Inertia::render('Dashboard', [
            'photos' => $this->photos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'image', 'max:10240'],
        ]);

        foreach ($validated['photos'] as $uploadedPhoto) {
            $path = $uploadedPhoto->store('photos', 'public');

            Photo::query()->create([
                'name' => $uploadedPhoto->getClientOriginalName(),
                'path' => $path,
            ]);
        }

        return to_route('dashboard')->with('success', 'Foto\'s zijn toegevoegd.');
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        if (! Storage::disk('public')->delete($photo->path)) {
            throw new RuntimeException("The photo [{$photo->path}] could not be deleted.");
        }

        $photo->delete();

        return to_route('dashboard')->with('success', 'De foto is verwijderd.');
    }

    private function photos(): array
    {
        return Photo::query()
            ->latest()
            ->get()
            ->map(fn (Photo $photo) => [
                'id' => $photo->id,
                'name' => $photo->name,
                'url' => Storage::disk('public')->url($photo->path),
            ])
            ->all();
    }
}