<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TravelReportRequest;
use App\Models\Region;
use App\Models\TravelReport;
use App\Support\ImageUploadService;
use App\Support\OpenAiImageGenerator;
use App\Support\TravelReportWriter;
use Illuminate\Http\Request;
use Throwable;

class TravelReportController extends Controller
{
    public function index(Request $request)
    {
        $query = TravelReport::with('region')->withCount('media');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        $reports = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function create()
    {
        return view('admin.reports.form', [
            'report' => new TravelReport(),
            'regions' => Region::orderBy('name')->get(),
        ]);
    }

    public function store(TravelReportRequest $request)
    {
        $report = TravelReport::create($this->fieldsFromRequest($request));

        $this->handleUploads($request, $report);

        return redirect()->route('admin.reports.edit', $report)->with('status', "Reisebericht \"{$report->title}\" wurde gespeichert.");
    }

    public function edit(TravelReport $report)
    {
        $report->load('media', 'region');

        return view('admin.reports.form', [
            'report' => $report,
            'regions' => Region::orderBy('name')->get(),
        ]);
    }

    public function update(TravelReportRequest $request, TravelReport $report)
    {
        $report->update($this->fieldsFromRequest($request, $report));

        $this->handleUploads($request, $report);

        return redirect()->route('admin.reports.edit', $report)->with('status', "Reisebericht \"{$report->title}\" wurde aktualisiert.");
    }

    public function destroy(TravelReport $report)
    {
        $title = $report->title;
        $report->delete();

        return redirect()->route('admin.reports.index')->with('status', "Reisebericht \"{$title}\" wurde gelöscht.");
    }

    public function preview(Request $request, TravelReport $report)
    {
        return app(\App\Http\Controllers\TravelReportController::class)->show($request, $report, preview: true);
    }

    public function generateAiDraft(Request $request)
    {
        $data = $request->validate([
            'ai_topic' => ['required', 'string', 'max:255'],
            'ai_context' => ['nullable', 'string', 'max:500'],
            'ai_author_name' => ['nullable', 'string', 'max:255'],
        ]);

        set_time_limit(120);

        try {
            $draft = TravelReportWriter::draft($data['ai_topic'], $data['ai_context'] ?? null);
        } catch (Throwable $e) {
            return back()->withErrors(['ai_topic' => $e->getMessage()])->withInput();
        }

        $faq = collect($draft['faq'] ?? [])
            ->filter(fn ($pair) => filled($pair['question'] ?? null) && filled($pair['answer'] ?? null))
            ->values()
            ->all();

        $report = TravelReport::create([
            'title' => $draft['title'],
            'excerpt' => mb_substr($draft['excerpt'] ?? $draft['title'], 0, 255),
            'content' => $draft['content'],
            'author_name' => filled($data['ai_author_name'] ?? null) ? $data['ai_author_name'] : $request->user()->name,
            'seo_title' => $draft['seo_title'] ?? null,
            'seo_description' => $draft['seo_description'] ?? null,
            'og_description' => $draft['og_description'] ?? null,
            'faq' => $faq,
            'is_published' => false,
            'ai_generated' => true,
        ]);

        return redirect()->route('admin.reports.edit', $report)
            ->with('status', 'KI-Entwurf wurde erstellt (unveröffentlicht). Bitte alle Angaben prüfen, bevor du den Bericht veröffentlichst.')
            ->with('imageSuggestions', $draft['image_suggestions'] ?? [])
            ->with('internalLinkSuggestions', $draft['internal_link_suggestions'] ?? []);
    }

    public function generateAiText(Request $request, TravelReport $report)
    {
        $request->validate([
            'ai_topic' => ['required', 'string', 'max:255'],
            'ai_context' => ['nullable', 'string', 'max:500'],
        ]);

        set_time_limit(120);

        try {
            $content = TravelReportWriter::write(
                $request->string('ai_topic')->toString(),
                $request->string('ai_context')->toString() ?: null,
            );
        } catch (Throwable $e) {
            return back()->withErrors(['ai_topic' => $e->getMessage()])->withInput();
        }

        $report->update(['content' => $content, 'ai_generated' => true]);

        return back()->with('status', 'Text wurde per KI generiert. Bitte vor der Veröffentlichung prüfen.');
    }

    public function generateAiImage(Request $request, TravelReport $report)
    {
        $request->validate([
            'ai_prompt' => ['required', 'string', 'max:600'],
        ]);

        set_time_limit(180);

        try {
            $contents = OpenAiImageGenerator::generate($request->string('ai_prompt')->toString());
        } catch (Throwable $e) {
            return back()->withErrors(['ai_prompt' => $e->getMessage()])->withInput();
        }

        $isCover = $report->media()->where('is_cover', true)->doesntExist();
        $path = ImageUploadService::storeBinary($contents, "reports/{$report->slug}", $report->slug.'-ki');
        ImageUploadService::attach($report, $path, $report->title, $isCover, (int) $report->media()->max('sort_order') + 1, 'ai');

        return back()->with('status', 'KI-Bild wurde erstellt und hinzugefügt.');
    }

    private function fieldsFromRequest(TravelReportRequest $request, ?TravelReport $existing = null): array
    {
        $data = $request->safe()->except(['cover_image', 'gallery_images']);
        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published'] && ! ($existing?->published_at)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    private function handleUploads(Request $request, TravelReport $report): void
    {
        if ($request->hasFile('cover_image')) {
            $report->media()->where('is_cover', true)->update(['is_cover' => false]);
            $path = ImageUploadService::store($request->file('cover_image'), "reports/{$report->slug}", $report->slug);
            ImageUploadService::attach($report, $path, $report->title, true, 0);
        }

        if ($request->hasFile('gallery_images')) {
            $nextOrder = (int) $report->media()->max('sort_order') + 1;
            foreach ($request->file('gallery_images') as $file) {
                $path = ImageUploadService::store($file, "reports/{$report->slug}", $report->slug);
                ImageUploadService::attach($report, $path, $report->title, false, $nextOrder++);
            }
        }
    }
}
