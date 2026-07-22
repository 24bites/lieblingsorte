<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\TravelReport;
use App\Models\TravelTip;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $regions = Region::published()->orderBy('sort_order')->get();
        $tips = TravelTip::published()->with('region')->get();
        $reports = TravelReport::published()->get();

        $xml = view('sitemap', compact('regions', 'tips', 'reports'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
