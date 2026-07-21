<?php

namespace App\Http\Controllers;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('travelTips')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $tips = $category->travelTips()
            ->published()
            ->with(['region', 'media', 'labels'])
            ->orderBy('sort_order')
            ->paginate(12);

        return view('categories.show', compact('category', 'tips'));
    }
}
