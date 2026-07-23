<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Private landing page. Authenticated builders go straight to their desk.
     */
    public function index()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('home');
    }
}
