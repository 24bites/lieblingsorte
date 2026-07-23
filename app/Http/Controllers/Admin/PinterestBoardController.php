<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinterestBoard;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PinterestBoardController extends Controller
{
    public function index()
    {
        $boards = PinterestBoard::with('region')->orderBy('type')->orderBy('name')->get();

        $assignedRegionIds = $boards->pluck('region_id')->filter();
        $regionsWithoutBoard = Region::published()
            ->whereNotIn('id', $assignedRegionIds)
            ->orderBy('name')
            ->get();

        return view('admin.social-hub.pinterest-boards', compact('boards', 'regionsWithoutBoard'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['region', 'topic'])],
            'name' => ['required', 'string', 'max:255'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['type'] === 'region' && blank($data['region_id'] ?? null)) {
            return back()->withErrors(['region_id' => 'Bitte eine Region auswählen.'])->withInput();
        }

        PinterestBoard::create([
            'type' => $data['type'],
            'name' => $data['name'],
            'region_id' => $data['type'] === 'region' ? $data['region_id'] : null,
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('status', 'Board wurde angelegt.');
    }

    public function update(Request $request, PinterestBoard $board)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $board->update($data);

        return back()->with('status', 'Board wurde aktualisiert.');
    }

    public function destroy(PinterestBoard $board)
    {
        if ($board->pins()->exists()) {
            return back()->withErrors(['board' => 'Dieses Board wird noch von Pins verwendet und kann nicht gelöscht werden.']);
        }

        $board->delete();

        return back()->with('status', 'Board wurde gelöscht.');
    }
}
