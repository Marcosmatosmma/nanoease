<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class OnboardingController extends Controller
{
    public function index()
    {
        return Inertia::render('Onboarding/Index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'segment' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'company_size' => 'required|string|max:255',
        ]);

        $request->user()->update($validated);

        return Redirect::route('dashboard');
    }
}
