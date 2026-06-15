<?php

namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcessController extends Controller
{
    public function index()
    {
        return Inertia::render('Process/process-list');
    }

    public function create()
    {
        return Inertia::render('Process/create');
    }
}
