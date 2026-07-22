<?php

namespace App\Http\Controllers;

use App\Models\Media;

class ImageCreditsController extends Controller
{
    public function index()
    {
        $credited = Media::with('mediable')
            ->where(function ($query) {
                $query->whereNotNull('credit_author')
                    ->orWhereNotNull('credit_license')
                    ->orWhereNotNull('credit_source_url');
            })
            ->orderBy('file_path')
            ->get()
            ->filter(fn (Media $media) => $media->mediable !== null);

        $grouped = $credited->groupBy(fn (Media $media) => $media->mediable->title ?? $media->mediable->name ?? '—');

        return view('legal.bildquellen', [
            'grouped' => $grouped,
            'total' => $credited->count(),
        ]);
    }
}
