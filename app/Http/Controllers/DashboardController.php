<?php

namespace App\Http\Controllers;

use App\Support\BranchContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BranchContext $branchContext): Response
    {
        return Inertia::render('dashboard', [
            'currentBranch' => $branchContext->current()
                ? ['id' => $branchContext->current()->id, 'name' => $branchContext->current()->name]
                : null,
        ]);
    }
}
