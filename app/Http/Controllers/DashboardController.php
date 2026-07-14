<?php

namespace App\Http\Controllers;

use App\Models\Document;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total'     => Document::count(),
            'processed' => Document::where('status', 'processed')->count(),
            'confirmed' => Document::where('status', 'confirmed')->count(),
            'error'     => Document::where('status', 'error')->count(),
        ];

        $recentDocs = Document::whereIn('status', ['processed', 'confirmed'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact('stats', 'recentDocs'));
    }
}
