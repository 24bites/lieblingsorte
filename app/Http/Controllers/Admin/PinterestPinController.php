<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinterestBoard;
use App\Models\PinterestPin;
use App\Models\Region;
use App\Models\TravelTip;
use App\Support\PinImageComposer;
use App\Support\PinterestApiClient;
use App\Support\PinterestConfig;
use App\Support\PinterestPinWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class PinterestPinController extends Controller
{
    private const TYPE_MAP = [
        'region' => Region::class,
        'tip' => TravelTip::class,
    ];

    public function index(Request $request)
    {
        $pins = PinterestPin::with(['board', 'featurable'])
            ->orderByRaw("FIELD(status, 'draft', 'approved', 'scheduled', 'failed', 'posted')")
            ->orderByDesc('created_at')
            ->get();

        $type = $request->string('type')->toString();
        $type = array_key_exists($type, self::TYPE_MAP) ? $type : 'region';
        $modelClass = self::TYPE_MAP[$type];
        $titleColumn = $type === 'region' ? 'name' : 'title';

        $query = $modelClass::query()->published();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where($titleColumn, 'like', "%{$search}%");
        }

        $items = $query->orderBy($titleColumn)->limit(30)->get();

        return view('admin.social-hub.pinterest-pins', [
            'pins' => $pins,
            'boards' => PinterestBoard::orderBy('type')->orderBy('name')->get(),
            'angles' => PinterestPinWriter::ANGLES,
            'items' => $items,
            'type' => $type,
            'openAiConfigured' => PinterestPinWriter::isConfigured(),
            'pinterestConfigured' => PinterestConfig::isConfigured(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPE_MAP))],
            'id' => ['required', 'integer'],
            'angle' => ['required', Rule::in(array_keys(PinterestPinWriter::ANGLES))],
            'board_ids' => ['required', 'array', 'min:1'],
            'board_ids.*' => ['integer', 'exists:pinterest_boards,id'],
        ]);

        $item = self::TYPE_MAP[$data['type']]::findOrFail($data['id']);
        $shareData = $item->socialShareData();
        $coverImage = $item->coverImage();

        if (! $coverImage) {
            return back()->withErrors(['generate' => 'Dieser Eintrag hat kein Titelbild, es kann kein Pin erzeugt werden.']);
        }

        try {
            $brief = PinterestPinWriter::write($data['angle'], $shareData);

            $imagePath = PinImageComposer::compose(
                $coverImage->displayPath(),
                $brief['overlay_headline'],
                $brief['overlay_subline'],
                'pins/'.$data['type'].'-'.$item->id,
                $data['angle']
            );
        } catch (Throwable $e) {
            return back()->withErrors(['generate' => $e->getMessage()]);
        }

        if (! $imagePath) {
            return back()->withErrors(['generate' => 'Das Pin-Bild konnte nicht erzeugt werden.']);
        }

        foreach ($data['board_ids'] as $boardId) {
            PinterestPin::create([
                'featurable_type' => $item::class,
                'featurable_id' => $item->id,
                'board_id' => $boardId,
                'variant_label' => $data['angle'],
                'overlay_headline' => $brief['overlay_headline'],
                'overlay_subline' => $brief['overlay_subline'],
                'generated_image_path' => $imagePath,
                'pin_title' => $brief['pin_title'],
                'pin_description' => $brief['pin_description'],
                'status' => 'draft',
            ]);
        }

        return redirect()->route('admin.pinterest-pins.index')->with('status', 'Pin wurde als Entwurf angelegt.');
    }

    public function show(PinterestPin $pin)
    {
        $pin->load(['board', 'featurable']);

        return view('admin.social-hub.pinterest-pin-show', [
            'pin' => $pin,
            'pinterestConfigured' => PinterestConfig::isConfigured(),
        ]);
    }

    public function update(Request $request, PinterestPin $pin)
    {
        $data = $request->validate([
            'pin_title' => ['required', 'string', 'max:255'],
            'pin_description' => ['required', 'string', 'max:2000'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $pin->update($data);

        return back()->with('status', 'Pin wurde aktualisiert.');
    }

    public function approve(PinterestPin $pin)
    {
        if ($pin->status !== 'draft') {
            return back()->withErrors(['approve' => 'Nur Entwürfe können freigegeben werden.']);
        }

        $pin->update(['status' => 'approved']);

        return back()->with('status', 'Pin wurde freigegeben.');
    }

    public function publish(PinterestPin $pin)
    {
        if (! PinterestConfig::isConfigured()) {
            return back()->withErrors(['publish' => 'Pinterest ist noch nicht verbunden. Die Veröffentlichung folgt, sobald die Pinterest-App eingerichtet ist.']);
        }

        if (! in_array($pin->status, ['approved', 'failed'], true)) {
            return back()->withErrors(['publish' => 'Nur freigegebene Pins können veröffentlicht werden.']);
        }

        $accessToken = PinterestConfig::validAccessToken();

        if (! $accessToken) {
            return back()->withErrors(['publish' => 'Pinterest-Verbindung ist abgelaufen. Bitte in den Einstellungen erneut verbinden.']);
        }

        $pin->loadMissing(['board', 'featurable']);

        try {
            $board = $pin->board;

            if (blank($board->pinterest_board_id)) {
                $board->update([
                    'pinterest_board_id' => PinterestApiClient::createBoard($accessToken, $board->name, $board->description),
                ]);
            }

            $link = $pin->featurable->socialShareData()['url'];

            $pinterestPinId = PinterestApiClient::createPin(
                $accessToken,
                $board->pinterest_board_id,
                $pin->pin_title,
                $pin->pin_description,
                $link,
                $pin->image_url,
            );

            $pin->update([
                'status' => 'posted',
                'posted_at' => now(),
                'pinterest_pin_id' => $pinterestPinId,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $pin->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            return back()->withErrors(['publish' => 'Veröffentlichung fehlgeschlagen: '.$e->getMessage()]);
        }

        return back()->with('status', 'Pin wurde auf Pinterest veröffentlicht.');
    }

    public function destroy(PinterestPin $pin)
    {
        if ($pin->generated_image_path) {
            Storage::disk('public')->delete($pin->generated_image_path);
        }

        $pin->delete();

        return redirect()->route('admin.pinterest-pins.index')->with('status', 'Pin wurde gelöscht.');
    }
}
