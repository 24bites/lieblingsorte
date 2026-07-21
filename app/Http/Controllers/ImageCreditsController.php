<?php

namespace App\Http\Controllers;

class ImageCreditsController extends Controller
{
    public function index()
    {
        $path = storage_path('app/credits.json');
        $credits = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $credits = is_array($credits) ? $credits : [];

        $grouped = collect($credits)
            ->sortBy('file')
            ->groupBy('used_for');

        return view('legal.bildquellen', [
            'grouped' => $grouped,
            'total' => count($credits),
        ]);
    }
}
