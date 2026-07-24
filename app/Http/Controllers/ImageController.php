<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\World;
use App\Support\ImageOwners;
use App\Support\Uploads;
use Illuminate\Http\Request;

/**
 * Galleries for any lore record — characters and every location tier — behind
 * one controller. The owner is addressed by its morph alias + id
 * (`/galeri/kota/12`), the same short keys used elsewhere for locations.
 */
class ImageController extends Controller
{
    public function store(Request $request, World $world, string $type, string $id)
    {
        $this->authorize('update', $world);

        $owner = ImageOwners::resolve($world, $type, $id);

        $data = $request->validate([
            'images' => ['required_without:image_url', 'array', 'max:12'],
            'images.*' => ['image', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:255'],
        ], [
            'images.required_without' => 'Pilih berkas gambar atau isi tautan gambar.',
            'images.*.image' => 'Setiap berkas harus berupa gambar.',
            'images.*.max' => 'Tiap gambar maksimal 4MB.',
            'images.max' => 'Maksimal 12 gambar sekali unggah.',
        ]);

        $caption = $data['caption'] ?? null;
        $position = $owner->nextImagePosition();
        $added = 0;

        foreach ($request->file('images', []) as $file) {
            $owner->images()->create([
                'world_id' => $world->id,
                'path' => Uploads::store($file, 'galeri'),
                'caption' => $caption,
                'position' => $position++,
            ]);
            $added++;
        }

        if ($url = trim((string) $request->input('image_url', ''))) {
            $owner->images()->create([
                'world_id' => $world->id,
                'path' => $url,
                'caption' => $caption,
                'position' => $position++,
            ]);
            $added++;
        }

        return back()->with('status', "{$added} gambar ditambahkan ke galeri.");
    }

    public function destroy(World $world, Image $image)
    {
        $this->authorize('update', $world);
        abort_unless($image->world_id === $world->id, 404);

        // Only this row; the model's deleting hook removes its uploaded file.
        $image->delete();

        return back()->with('status', 'Gambar dihapus dari galeri.');
    }

    /** Promote a gallery image to the record's cover (portrait / map). */
    public function cover(World $world, Image $image)
    {
        $this->authorize('update', $world);
        abort_unless($image->world_id === $world->id, 404);

        $owner = $image->imageable;
        abort_unless($owner !== null, 404);

        $owner->update([$owner->coverColumn() => $image->path]);

        return back()->with('status', 'Gambar dijadikan sampul.');
    }
}
