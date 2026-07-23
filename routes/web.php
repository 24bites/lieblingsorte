<?php

use App\Http\Controllers\Admin\AiRegionGeneratorController;
use App\Http\Controllers\Admin\AiSuggestionController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LabelController as AdminLabelController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PinterestBoardController;
use App\Http\Controllers\Admin\PinterestFeedCurationController;
use App\Http\Controllers\Admin\PinterestOAuthController;
use App\Http\Controllers\Admin\PinterestPinController;
use App\Http\Controllers\Admin\RegionController as AdminRegionController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SocialHubController;
use App\Http\Controllers\Admin\TravelReportController as AdminTravelReportController;
use App\Http\Controllers\Admin\TravelTipController as AdminTravelTipController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageCreditsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PinterestFeedController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RssFeedController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TravelReportController;
use App\Http\Controllers\TravelTipController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/regionen', [RegionController::class, 'index'])->name('regions.index');
Route::get('/kategorien', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/kategorie/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/reiseberichte', [TravelReportController::class, 'index'])->name('reports.index');
Route::get('/reiseberichte/{report:slug}', [TravelReportController::class, 'show'])->name('reports.show');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/suche', [SearchController::class, 'index'])->name('search');
    Route::get('/suche/vorschlaege', [SearchController::class, 'suggestions'])->name('search.suggestions');
});

Route::get('/newsletter', [NewsletterController::class, 'show'])->name('newsletter.show');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('newsletter.store');
Route::get('/newsletter/danke', [NewsletterController::class, 'thanks'])->name('newsletter.thanks');
Route::get('/newsletter/bestaetigen/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/abmelden/{token}', [NewsletterController::class, 'unsubscribeShow'])->name('newsletter.unsubscribe.show');
Route::post('/newsletter/abmelden/{token}', [NewsletterController::class, 'unsubscribeDestroy'])->name('newsletter.unsubscribe.destroy');
Route::get('/favoriten', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favoriten/{travelTip}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/pinterest-feed.xml', [PinterestFeedController::class, 'index'])->name('pinterest-feed');
Route::get('/feed.xml', [RssFeedController::class, 'index'])->name('feed');
Route::view('/impressum', 'legal.impressum')->name('legal.impressum');
Route::view('/datenschutz', 'legal.datenschutz')->name('legal.datenschutz');
Route::get('/bildquellen', [ImageCreditsController::class, 'index'])->name('legal.bildquellen');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('regionen', [AdminRegionController::class, 'index'])->name('regions.index');
        Route::get('regionen/erstellen', [AdminRegionController::class, 'create'])->name('regions.create');
        Route::post('regionen', [AdminRegionController::class, 'store'])->name('regions.store');
        Route::get('regionen/{region}/bearbeiten', [AdminRegionController::class, 'edit'])->name('regions.edit');
        Route::put('regionen/{region}', [AdminRegionController::class, 'update'])->name('regions.update');
        Route::delete('regionen/{region}', [AdminRegionController::class, 'destroy'])->name('regions.destroy');
        Route::post('regionen/{region}/ki-bild', [AdminRegionController::class, 'generateAiImage'])->name('regions.ai-image');
        Route::get('regionen/{region}/vorschau', [AdminRegionController::class, 'preview'])->name('regions.preview');

        Route::get('ki-regionsgenerator', [AiRegionGeneratorController::class, 'create'])->name('ai-region-generator.create');
        Route::post('ki-regionsgenerator', [AiRegionGeneratorController::class, 'store'])->name('ai-region-generator.store');

        Route::get('ki-vorschlaege', [AiSuggestionController::class, 'index'])->name('ai-suggestions.index');
        Route::post('ki-vorschlaege/{region}/freigeben', [AiSuggestionController::class, 'approve'])->name('ai-suggestions.approve');
        Route::post('ki-vorschlaege/{region}/ablehnen', [AiSuggestionController::class, 'reject'])->name('ai-suggestions.reject');

        Route::get('reisetipps', [AdminTravelTipController::class, 'index'])->name('tips.index');
        Route::get('reisetipps/erstellen', [AdminTravelTipController::class, 'create'])->name('tips.create');
        Route::post('reisetipps', [AdminTravelTipController::class, 'store'])->name('tips.store');
        Route::get('reisetipps/{tip}/bearbeiten', [AdminTravelTipController::class, 'edit'])->name('tips.edit');
        Route::put('reisetipps/{tip}', [AdminTravelTipController::class, 'update'])->name('tips.update');
        Route::delete('reisetipps/{tip}', [AdminTravelTipController::class, 'destroy'])->name('tips.destroy');
        Route::post('reisetipps/{tip}/ki-bild', [AdminTravelTipController::class, 'generateAiImage'])->name('tips.ai-image');
        Route::get('reisetipps/{tip}/vorschau', [AdminTravelTipController::class, 'preview'])->name('tips.preview');

        Route::get('reiseberichte', [AdminTravelReportController::class, 'index'])->name('reports.index');
        Route::get('reiseberichte/erstellen', [AdminTravelReportController::class, 'create'])->name('reports.create');
        Route::post('reiseberichte/ki-entwurf', [AdminTravelReportController::class, 'generateAiDraft'])->name('reports.ai-draft');
        Route::post('reiseberichte', [AdminTravelReportController::class, 'store'])->name('reports.store');
        Route::get('reiseberichte/{report}/bearbeiten', [AdminTravelReportController::class, 'edit'])->name('reports.edit');
        Route::put('reiseberichte/{report}', [AdminTravelReportController::class, 'update'])->name('reports.update');
        Route::delete('reiseberichte/{report}', [AdminTravelReportController::class, 'destroy'])->name('reports.destroy');
        Route::post('reiseberichte/{report}/ki-text', [AdminTravelReportController::class, 'generateAiText'])->name('reports.ai-text');
        Route::post('reiseberichte/{report}/ki-bild', [AdminTravelReportController::class, 'generateAiImage'])->name('reports.ai-image');
        Route::get('reiseberichte/{report}/vorschau', [AdminTravelReportController::class, 'preview'])->name('reports.preview');

        Route::get('social-hub', [SocialHubController::class, 'index'])->name('social-hub.index');
        Route::post('social-hub/generieren', [SocialHubController::class, 'generate'])->name('social-hub.generate');
        Route::get('social-hub/pinterest-feed', [PinterestFeedCurationController::class, 'index'])->name('pinterest-feed-curation.index');
        Route::post('social-hub/pinterest-feed', [PinterestFeedCurationController::class, 'store'])->name('pinterest-feed-curation.store');
        Route::delete('social-hub/pinterest-feed/{feature}', [PinterestFeedCurationController::class, 'destroy'])->name('pinterest-feed-curation.destroy');
        Route::patch('social-hub/pinterest-feed/{feature}/hoch', [PinterestFeedCurationController::class, 'moveUp'])->name('pinterest-feed-curation.up');
        Route::patch('social-hub/pinterest-feed/{feature}/runter', [PinterestFeedCurationController::class, 'moveDown'])->name('pinterest-feed-curation.down');
        Route::get('social-hub/pinterest-boards', [PinterestBoardController::class, 'index'])->name('pinterest-boards.index');
        Route::post('social-hub/pinterest-boards', [PinterestBoardController::class, 'store'])->name('pinterest-boards.store');
        Route::put('social-hub/pinterest-boards/{board}', [PinterestBoardController::class, 'update'])->name('pinterest-boards.update');
        Route::delete('social-hub/pinterest-boards/{board}', [PinterestBoardController::class, 'destroy'])->name('pinterest-boards.destroy');
        Route::get('social-hub/pinterest-pins', [PinterestPinController::class, 'index'])->name('pinterest-pins.index');
        Route::post('social-hub/pinterest-pins', [PinterestPinController::class, 'store'])->name('pinterest-pins.store');
        Route::get('social-hub/pinterest-pins/{pin}', [PinterestPinController::class, 'show'])->name('pinterest-pins.show');
        Route::put('social-hub/pinterest-pins/{pin}', [PinterestPinController::class, 'update'])->name('pinterest-pins.update');
        Route::post('social-hub/pinterest-pins/{pin}/freigeben', [PinterestPinController::class, 'approve'])->name('pinterest-pins.approve');
        Route::post('social-hub/pinterest-pins/{pin}/veroeffentlichen', [PinterestPinController::class, 'publish'])->name('pinterest-pins.publish');
        Route::delete('social-hub/pinterest-pins/{pin}', [PinterestPinController::class, 'destroy'])->name('pinterest-pins.destroy');
        Route::get('social-hub/pinterest/connect', [PinterestOAuthController::class, 'connect'])->name('pinterest.connect');
        Route::get('social-hub/pinterest/callback', [PinterestOAuthController::class, 'callback'])->name('pinterest.callback');
        Route::post('social-hub/pinterest/disconnect', [PinterestOAuthController::class, 'disconnect'])->name('pinterest.disconnect');
        Route::get('social-hub/{socialPost}', [SocialHubController::class, 'show'])->name('social-hub.show');
        Route::put('social-hub/{socialPost}', [SocialHubController::class, 'update'])->name('social-hub.update');
        Route::post('social-hub/{socialPost}/senden', [SocialHubController::class, 'send'])->name('social-hub.send');
        Route::post('social-hub/{socialPost}/als-gesendet-markieren', [SocialHubController::class, 'markSent'])->name('social-hub.mark-sent');
        Route::delete('social-hub/{socialPost}', [SocialHubController::class, 'destroy'])->name('social-hub.destroy');

        Route::get('kategorien', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('kategorien/erstellen', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('kategorien', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('kategorien/{category}/bearbeiten', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('kategorien/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('kategorien/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('labels', [AdminLabelController::class, 'index'])->name('labels.index');
        Route::get('labels/erstellen', [AdminLabelController::class, 'create'])->name('labels.create');
        Route::post('labels', [AdminLabelController::class, 'store'])->name('labels.store');
        Route::get('labels/{label}/bearbeiten', [AdminLabelController::class, 'edit'])->name('labels.edit');
        Route::put('labels/{label}', [AdminLabelController::class, 'update'])->name('labels.update');
        Route::delete('labels/{label}', [AdminLabelController::class, 'destroy'])->name('labels.destroy');

        Route::get('medien', [AdminMediaController::class, 'index'])->name('media.index');
        Route::delete('medien/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::patch('medien/{media}/titelbild', [AdminMediaController::class, 'makeCover'])->name('media.cover');
        Route::patch('medien/{media}/hoch', [AdminMediaController::class, 'moveUp'])->name('media.up');
        Route::patch('medien/{media}/runter', [AdminMediaController::class, 'moveDown'])->name('media.down');
        Route::patch('medien/{media}/quelle', [AdminMediaController::class, 'updateCredit'])->name('media.credit');

        Route::get('einstellungen', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('einstellungen', [AdminSettingController::class, 'update'])->name('settings.update');

        Route::middleware('admin.role')->group(function () {
            Route::get('benutzer', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('benutzer/erstellen', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('benutzer', [AdminUserController::class, 'store'])->name('users.store');
            Route::get('benutzer/{editUser}/bearbeiten', [AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('benutzer/{editUser}', [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('benutzer/{editUser}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

// Dynamic public region & travel tip pages must stay last so they don't shadow the routes above.
Route::get('/{region:slug}', [RegionController::class, 'show'])->name('regions.show');
Route::get('/{region:slug}/{tipSlug}', [TravelTipController::class, 'show'])->name('tips.show');
