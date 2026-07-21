<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LabelController extends Controller
{
    public function index()
    {
        $labels = Label::withCount('travelTips')->orderBy('name')->get();

        return view('admin.labels.index', compact('labels'));
    }

    public function create()
    {
        return view('admin.labels.form', ['label' => new Label()]);
    }

    public function store(Request $request)
    {
        Label::create($this->validated($request));

        return redirect()->route('admin.labels.index')->with('status', 'Label wurde angelegt.');
    }

    public function edit(Label $label)
    {
        return view('admin.labels.form', compact('label'));
    }

    public function update(Request $request, Label $label)
    {
        $label->update($this->validated($request, $label->id));

        return redirect()->route('admin.labels.index')->with('status', 'Label wurde aktualisiert.');
    }

    public function destroy(Label $label)
    {
        $label->delete();

        return redirect()->route('admin.labels.index')->with('status', 'Label wurde gelöscht.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('labels', 'slug')->ignore($ignoreId)],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
