<?php

namespace App\Repositories;

use App\Models\Article;

class ArticleRepository
{
    /**
     * Get the last created article
     */
    public function getArticleByUrl(string $url): ?Article
    {
        return Article::where('url', '=', $url)->first();
    }

    /**
     * Get all articles sorted by a specified field
     *
     * @param string $field
     * @param string $direction (asc or desc)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllArticlesSorted(string $field, string $direction = 'asc')
    {
        return Article::orderBy($field, $direction)->select(['id', 'title', 'author', 'url', 'date'])->get();
    }

    public function getCount()
    {
        return Article::count();
    }

    public function getStats()
    {
        return Article::selectRaw('DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as count')
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('count', 'month')
            ->toArray();
    }
}
