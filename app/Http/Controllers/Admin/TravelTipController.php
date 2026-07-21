<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TravelTipRequest;
use App\Models\Category;
use App\Models\Label;
use App\Models\Region;
use App\Models\TravelTip;
use App\Support\ImageUploadService;
use App\Support\OpenAiImageGenerator;
use Illuminate\Http\Request;
use Throwable;

class TravelTipController extends Controller
{
    public function index(Request $request)
    {
        $query = TravelTip::with('region')->withCount('media');

        if ($regionId = $request->input('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tips = $query->orderBy('region_id')->orderBy('sort_order')->paginate(20)->withQueryString();
        $regions = Region::orderBy('name')->get();

        return view('admin.tips.index', compact('tips', 'regions'));
    }

    public function create()
    {
        return view('admin.tips.form', [
            'tip' => new TravelTip(),
            'regions' => Region::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'labels' => Label::orderBy('name')->get(),
        ]);
    }

    public function store(TravelTipRequest $request)
    {
        $tip = TravelTip::create($this->fieldsFromRequest($request));

        $tip->categories()->sync($request->input('categories', []));
        $tip->labels()->sync($request->input('labels', []));

        $this->handleUploads($request, $tip);

        return redirect()->route('admin.tips.index')->with('status', "Reisetipp \"{$tip->title}\" wurde gespeichert.");
    }

    public function edit(TravelTip $tip)
    {
        $tip->load('media', 'categories', 'labels');

        return view('admin.tips.form', [
            'tip' => $tip,
            'regions' => Region::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'labels' => Label::orderBy('name')->get(),
        ]);
    }

    public function update(TravelTipRequest $request, TravelTip $tip)
    {
        $tip->update($this->fieldsFromRequest($request));

        $tip->categories()->sync($request->input('categories', []));
        $tip->labels()->sync($request->input('labels', []));

        $this->handleUploads($request, $tip);

        return redirect()->route('admin.tips.index')->with('status', "Reisetipp \"{$tip->title}\" wurde aktualisiert.");
    }

    public function destroy(TravelTip $tip)
    {
        $title = $tip->title;
        $tip->delete();

        return redirect()->route('admin.tips.index')->with('status', "Reisetipp \"{$title}\" wurde gelöscht.");
    }

    public function preview(Request $request, TravelTip $tip)
    {
        $tip->loadMissing('region');

        return app(\App\Http\Controllers\TravelTipController::class)->show($request, $tip->region, $tip->slug, preview: true);
    }

    public function generateAiImage(Request $request, TravelTip $tip)
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

        $isCover = $tip->media()->where('is_cover', true)->doesntExist();
        $path = ImageUploadService::storeBinary($contents, "tips/{$tip->slug}", $tip->slug.'-ki');
        ImageUploadService::attach($tip, $path, $tip->title, $isCover, (int) $tip->media()->max('sort_order') + 1);

        return back()->with('status', 'KI-Bild wurde erstellt und hinzugefügt.');
    }

    private function fieldsFromRequest(TravelTipRequest $request): array
    {
        $data = $request->safe()->except(['categories', 'labels', 'cover_image', 'gallery_images', 'highlights']);

        $booleans = ['family_friendly', 'stroller_friendly', 'dog_friendly', 'indoor', 'free_entry', 'featured', 'is_published'];
        foreach ($booleans as $field) {
            $data[$field] = $request->boolean($field);
        }

        $highlights = collect($request->input('highlights', []))
            ->filter(fn ($h) => trim((string) $h) !== '')
            ->values()
            ->all();
        $data['highlights'] = $highlights;

        return $data;
    }

    private function handleUploads(Request $request, TravelTip $tip): void
    {
        if ($request->hasFile('cover_image')) {
            $tip->media()->where('is_cover', true)->update(['is_cover' => false]);
            $path = ImageUploadService::store($request->file('cover_image'), "tips/{$tip->slug}", $tip->slug);
            ImageUploadService::attach($tip, $path, $tip->title, true, 0);
        }

        if ($request->hasFile('gallery_images')) {
            $nextOrder = (int) $tip->media()->max('sort_order') + 1;
            foreach ($request->file('gallery_images') as $file) {
                $path = ImageUploadService::store($file, "tips/{$tip->slug}", $tip->slug);
                ImageUploadService::attach($tip, $path, $tip->title, false, $nextOrder++);
            }
        }
    }
}
