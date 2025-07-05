<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class SellController extends Controller
{
    public function index()
    {
        return Inertia::render('Sell/Index', [
            'title' => 'Sell your products online',
        ]);
    }
}
