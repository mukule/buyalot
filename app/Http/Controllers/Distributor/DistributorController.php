<?php

namespace App\Http\Controllers\Distributor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DistributorController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Distributor/Dashboard');
    }
}
