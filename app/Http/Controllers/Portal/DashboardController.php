<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $penyewa = $request->user()->penyewa()->with('branch')->first();

        return Inertia::render('portal/dashboard', [
            'penyewa' => $penyewa ? [
                'id' => $penyewa->id,
                'name' => $penyewa->name,
                'branch' => $penyewa->branch?->name,
            ] : null,
        ]);
    }
}
