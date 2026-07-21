<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegionRequest;
use App\Models\Label;
use App\Models\Region;
use App\Support\ImageUploadService;
use App\Support\OpenAiImageGenerator;
use Illuminate\Http\Request;
use Throwable;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $query = Region::withCount('travelTips');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $regions = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        return view('admin.regions.index', compact('regions'));
    }

    public function create()
    {
        $labels = Label::orderBy('name')->get();

        return view('admin.regions.form', [
            'region' => new Region(),
            'labels' => $labels,
        ]);
    }

    public function store(RegionRequest $request)
    {
        $region = Region::create($request->safe()->except(['labels', 'hero_image', 'gallery_images', 'is_published']) + [
            'is_published' => $request->boolean('is_published'),
        ]);

        $region->labels()->sync($request->input('labels', []));

        $this->handleUploads($request, $region);

        return redirect()->route('admin.regions.index')->with('status', "Region \"{$region->name}\" wurde gespeichert.");
    }

    public function edit(Region $region)
    {
        $labels = Label::orderBy('name')->get();
        $region->load('media', 'labels');

        return view('admin.regions.form', compact('region', 'labels'));
    }

    public function update(RegionRequest $request, Region $region)
    {
        $region->update($request->safe()->except(['labels', 'hero_image', 'gallery_images', 'is_published']) + [
            'is_published' => $request->boolean('is_published'),
        ]);

        $region->labels()->sync($request->input('labels', []));

        $this->handleUploads($request, $region);

        return redirect()->route('admin.regions.index')->with('status', "Region \"{$region->name}\" wurde aktualisiert.");
    }

    public function destroy(Region $region)
    {
        $name = $region->name;
        $region->delete();

        return redirect()->route('admin.regions.index')->with('status', "Region \"{$name}\" wurde gelöscht.");
    }

    public function preview(Request $request, Region $region)
    {
        return app(\App\Http\Controllers\RegionController::class)->show($request, $region, preview: true);
    }

    public function generateAiImage(Request $request, Region $region)
    {
        $request->validate([
            'ai_prompt' => ['required', 'string', 'max:600'],
        ]);

        // Image generation can take longer than PHP's default max_execution_time
        // (commonly 30s on shared php.ini configs).
        set_time_limit(180);

        try {
            $contents = OpenAiImageGenerator::generate($request->string('ai_prompt')->toString());
        } catch (Throwable $e) {
            return back()->withErrors(['ai_prompt' => $e->getMessage()])->withInput();
        }

        $isCover = $region->media()->where('is_cover', true)->doesntExist();
        $path = ImageUploadService::storeBinary($contents, "regions/{$region->slug}", $region->slug.'-ki');
        ImageUploadService::attach($region, $path, $region->name, $isCover, (int) $region->media()->max('sort_order') + 1);

        if ($isCover) {
            $region->update(['hero_image' => $path]);
        }

        return back()->with('status', 'KI-Bild wurde erstellt und hinzugefügt.');
    }

    private function handleUploads(Request $request, Region $region): void
    {
        if ($request->hasFile('hero_image')) {
            $region->media()->where('is_cover', true)->update(['is_cover' => false]);
            $path = ImageUploadService::store($request->file('hero_image'), "regions/{$region->slug}", $region->slug.'-hero');
            ImageUploadService::attach($region, $path, $region->name, true, 0);
            $region->update(['hero_image' => $path]);
        }

        if ($request->hasFile('gallery_images')) {
            $nextOrder = (int) $region->media()->max('sort_order') + 1;
            foreach ($request->file('gallery_images') as $file) {
                $path = ImageUploadService::store($file, "regions/{$region->slug}", $region->slug);
                ImageUploadService::attach($region, $path, $region->name, false, $nextOrder++);
            }
        }
    }
}
