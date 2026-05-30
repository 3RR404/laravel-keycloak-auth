<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard', [
            'user' => $request->user(),
        ]);
    }

    /** Admin-only area — protected by keycloak.role middleware in routes. */
    public function admin(Request $request): View
    {
        return view('admin', [
            'user' => $request->user(),
        ]);
    }
}
