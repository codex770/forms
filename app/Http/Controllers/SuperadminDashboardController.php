<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class SuperadminDashboardController extends Controller
{
    /**
     * Display the superadmin dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('SuperadminDashboard');
    }
}
