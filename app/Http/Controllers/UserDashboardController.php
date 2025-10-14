<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('UserDashboard');
    }
}
