<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Sign;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'categories' => Category::withCount('signs')->orderBy('sort_order')->get(),
            'signCount' => Sign::count(),
        ]);
    }
}
