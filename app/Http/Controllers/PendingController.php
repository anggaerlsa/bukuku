<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * The holding page an account sits on until a superadmin approves it.
 * Deliberately outside the `approved` middleware — it is the one place an
 * unapproved account is allowed to be.
 */
class PendingController extends Controller
{
    public function __invoke(Request $request)
    {
        // Sudah disetujui? Tak ada alasan menahannya di sini.
        if ($request->user()->isApproved()) {
            return redirect()->route('dashboard');
        }

        return view('auth.pending', ['user' => $request->user()]);
    }
}
