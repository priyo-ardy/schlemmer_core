<?php

namespace App\Http\Controllers\Pfmea;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PfmeaController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Pfmea/Index', [
            'page_title' => 'PFMEA List'
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Pfmea/Create', [
            'page_title' => 'Create PFMEA Document'
        ]);
    }
}
