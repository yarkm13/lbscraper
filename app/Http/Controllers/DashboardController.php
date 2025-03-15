<?php

namespace App\Http\Controllers;

use App\Repositories\ArticleRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stats = $this->articleRepository->getStats();
        $months = [];
        for ($i = 0; $i <= 5; $i++) {
            $date = now()->subMonths($i);
            $monthName = $date->format('F'); // Full month name (e.g., "January")
            $monthKey = $date->format('Y-m'); // YYYY-MM for lookup

            $months[] = [
                'month' => $monthName,
                'amount' => $stats[$monthKey] ?? 0, // Use retrieved count or 0 if no data
            ];
        }

        return Inertia::render('dashboard', [
            'total' => $this->articleRepository->getCount(),
            'stats' => $months,
        ]);
    }
}
