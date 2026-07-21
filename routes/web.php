<?php

use App\Http\Controllers\Admin\AiRegionGeneratorController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LabelController as AdminLabelController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\RegionController as AdminRegionController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TravelTipController as AdminTravelTipController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImageCreditsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TravelTipController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/regionen', [RegionController::class, 'index'])->name('regions.index');
Route::get('/kategorien', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/kategorie/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/suche', [SearchController::class, 'index'])->name('search');
    Route::get('/suche/vorschlaege', [SearchController::class, 'suggestions'])->name('search.suggestions');
});

Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');
Route::get('/favoriten', [FavoriteController::class, 'index'])->name('favorites.index');
Route::post('/favoriten/{travelTip}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
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

        Route::get('ki-regionsgenerator', [AiRegionGeneratorController::class, 'create'])->name('ai-region-generator.create');
        Route::post('ki-regionsgenerator', [AiRegionGeneratorController::class, 'store'])->name('ai-region-generator.store');

        Route::get('reisetipps', [AdminTravelTipController::class, 'index'])->name('tips.index');
        Route::get('reisetipps/erstellen', [AdminTravelTipController::class, 'create'])->name('tips.create');
        Route::post('reisetipps', [AdminTravelTipController::class, 'store'])->name('tips.store');
        Route::get('reisetipps/{tip}/bearbeiten', [AdminTravelTipController::class, 'edit'])->name('tips.edit');
        Route::put('reisetipps/{tip}', [AdminTravelTipController::class, 'update'])->name('tips.update');
        Route::delete('reisetipps/{tip}', [AdminTravelTipController::class, 'destroy'])->name('tips.destroy');
        Route::post('reisetipps/{tip}/ki-bild', [AdminTravelTipController::class, 'generateAiImage'])->name('tips.ai-image');

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

        Route::get('einstellungen', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('einstellungen', [AdminSettingController::class, 'update'])->name('settings.update');
    });
});

// Dynamic public region & travel tip pages must stay last so they don't shadow the routes above.
Route::get('/{region:slug}', [RegionController::class, 'show'])->name('regions.show');
Route::get('/{region:slug}/{tipSlug}', [TravelTipController::class, 'show'])->name('tips.show');
