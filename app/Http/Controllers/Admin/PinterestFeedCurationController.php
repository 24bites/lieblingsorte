<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinterestFeedFeature;
use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PinterestFeedCurationController extends Controller
{
    private const TYPE_MAP = [
        'region' => Region::class,
        'tip' => TravelTip::class,
    ];

    public function index(Request $request)
    {
        $featured = PinterestFeedFeature::with('featurable')->orderBy('sort_order')->get();
        $featuredKeys = $featured->map(fn (PinterestFeedFeature $f) => "{$f->featurable_type}:{$f->featurable_id}");

        $type = $request->string('type')->toString();
        $type = array_key_exists($type, self::TYPE_MAP) ? $type : 'region';
        $modelClass = self::TYPE_MAP[$type];
        $titleColumn = $type === 'region' ? 'name' : 'title';

        $query = $modelClass::query()->published();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where($titleColumn, 'like', "%{$search}%");
        }

        $items = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admin.social-hub.pinterest-feed', [
            'featured' => $featured,
            'items' => $items,
            'type' => $type,
            'featuredKeys' => $featuredKeys,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPE_MAP))],
            'id' => ['required', 'integer'],
        ]);

        $modelClass = self::TYPE_MAP[$data['type']];
        $item = $modelClass::findOrFail($data['id']);

        $alreadyFeatured = PinterestFeedFeature::where('featurable_type', $modelClass)
            ->where('featurable_id', $item->id)
            ->exists();

        if (! $alreadyFeatured) {
            PinterestFeedFeature::create([
                'featurable_type' => $modelClass,
                'featurable_id' => $item->id,
                'sort_order' => (int) PinterestFeedFeature::max('sort_order') + 1,
            ]);
        }

        return back()->with('status', 'Zum Pinterest-Feed hinzugefügt.');
    }

    public function destroy(PinterestFeedFeature $feature)
    {
        $feature->delete();

        return back()->with('status', 'Aus dem Pinterest-Feed entfernt.');
    }

    public function moveUp(PinterestFeedFeature $feature)
    {
        $this->swapWithNeighbor($feature, 'up');

        return back();
    }

    public function moveDown(PinterestFeedFeature $feature)
    {
        $this->swapWithNeighbor($feature, 'down');

        return back();
    }

    private function swapWithNeighbor(PinterestFeedFeature $feature, string $direction): void
    {
        $siblings = PinterestFeedFeature::orderBy('sort_order')->get();

        $index = $siblings->search(fn (PinterestFeedFeature $f) => $f->id === $feature->id);
        $neighborIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! $siblings->has($neighborIndex)) {
            return;
        }

        $neighbor = $siblings[$neighborIndex];
        $currentOrder = $feature->sort_order;
        $feature->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $currentOrder]);
    }
}
