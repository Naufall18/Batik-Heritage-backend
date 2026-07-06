<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    /** Daftar kategori (pohon: induk + anak). */
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('sort')
            ->get();

        return CategoryResource::collection($categories);
    }
}
