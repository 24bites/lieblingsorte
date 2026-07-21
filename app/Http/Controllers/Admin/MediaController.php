<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::with('mediable')->orderByDesc('created_at')->paginate(30);

        return view('admin.media.index', compact('media'));
    }

    public function destroy(Media $media)
    {
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return back()->with('status', 'Bild wurde gelöscht.');
    }

    public function makeCover(Media $media)
    {
        $media->mediable->media()->update(['is_cover' => false]);
        $media->update(['is_cover' => true]);

        return back()->with('status', 'Titelbild wurde aktualisiert.');
    }

    public function moveUp(Media $media)
    {
        $this->swapWithNeighbor($media, 'up');

        return back()->with('status', 'Reihenfolge wurde aktualisiert.');
    }

    public function moveDown(Media $media)
    {
        $this->swapWithNeighbor($media, 'down');

        return back()->with('status', 'Reihenfolge wurde aktualisiert.');
    }

    private function swapWithNeighbor(Media $media, string $direction): void
    {
        $siblings = Media::where('mediable_type', $media->mediable_type)
            ->where('mediable_id', $media->mediable_id)
            ->orderBy('sort_order')
            ->get();

        $index = $siblings->search(fn ($m) => $m->id === $media->id);
        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! $siblings->has($neighborIndex)) {
            return;
        }

        $neighbor = $siblings[$neighborIndex];
        $currentOrder = $media->sort_order;
        $media->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $currentOrder]);
    }
}
