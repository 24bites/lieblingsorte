<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\SocialPost;
use App\Models\TravelReport;
use App\Models\TravelTip;
use App\Support\OpenAiSocialCopywriter;
use App\Support\SocialShareLinks;
use App\Support\TelegramPublisher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class SocialHubController extends Controller
{
    private const TYPE_MAP = [
        'region' => Region::class,
        'tip' => TravelTip::class,
        'report' => TravelReport::class,
    ];

    public function index(Request $request)
    {
        $type = $request->string('type')->toString();
        $type = array_key_exists($type, self::TYPE_MAP) ? $type : 'region';
        $modelClass = self::TYPE_MAP[$type];
        $titleColumn = $type === 'region' ? 'name' : 'title';

        $query = $modelClass::query()->published()->with('socialPosts');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where($titleColumn, 'like', "%{$search}%");
        }

        $items = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admin.social-hub.index', [
            'items' => $items,
            'type' => $type,
            'platforms' => SocialPost::PLATFORMS,
            'openAiConfigured' => OpenAiSocialCopywriter::isConfigured(),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::TYPE_MAP))],
            'id' => ['required', 'integer'],
            'platform' => ['required', Rule::in(SocialPost::PLATFORMS)],
        ]);

        $postable = self::TYPE_MAP[$data['type']]::findOrFail($data['id']);
        $shareData = $postable->socialShareData();

        try {
            $caption = OpenAiSocialCopywriter::write($data['platform'], $shareData);
        } catch (Throwable $e) {
            return back()->withErrors(['generate' => $e->getMessage()]);
        }

        $socialPost = $postable->socialPosts()->updateOrCreate(
            ['platform' => $data['platform']],
            [
                'caption' => $caption,
                'link_url' => $shareData['url'],
                'image_url' => $shareData['image'],
                'status' => 'draft',
                'sent_at' => null,
                'error_message' => null,
            ],
        );

        return redirect()->route('admin.social-hub.show', $socialPost)->with('status', 'Beitrag wurde generiert.');
    }

    public function show(SocialPost $socialPost)
    {
        $shareLink = SocialShareLinks::build($socialPost->platform, $socialPost->link_url, $socialPost->caption, $socialPost->image_url);
        $canSendViaTelegram = $socialPost->platform === 'telegram' && TelegramPublisher::isConfigured();

        return view('admin.social-hub.show', compact('socialPost', 'shareLink', 'canSendViaTelegram'));
    }

    public function update(Request $request, SocialPost $socialPost)
    {
        $data = $request->validate(['caption' => ['required', 'string', 'max:2000']]);
        $socialPost->update($data);

        return back()->with('status', 'Text wurde aktualisiert.');
    }

    public function send(SocialPost $socialPost)
    {
        abort_unless($socialPost->platform === 'telegram', 404);

        try {
            TelegramPublisher::send($socialPost->caption, $socialPost->link_url, $socialPost->image_url);
        } catch (Throwable $e) {
            $socialPost->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            return back()->withErrors(['send' => $e->getMessage()]);
        }

        $socialPost->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

        return back()->with('status', 'Beitrag wurde an Telegram gesendet.');
    }

    public function markSent(SocialPost $socialPost)
    {
        $socialPost->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);

        return back()->with('status', 'Als gesendet markiert.');
    }

    public function destroy(SocialPost $socialPost)
    {
        $socialPost->delete();

        return redirect()->route('admin.social-hub.index')->with('status', 'Entwurf wurde gelöscht.');
    }
}
