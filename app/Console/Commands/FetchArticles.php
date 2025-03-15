<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\ArticleRepository;
use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class FetchArticles extends Command
{
    protected const BLOG_URL = 'https://laravel-news.com/category/news';
    protected const IMPERSONATE_COMMAND = './curl-impersonate/curl_chrome116';

    protected $signature = 'articles:fetch {limit=0} {debug?}';
    protected $description = 'Fetch new articles from ' . self::BLOG_URL;
    private ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        parent::__construct();
        $this->articleRepository = $articleRepository;
        $this->cutoffDate = now()->subWeeks(10);
        $this->cutoffReached = false;
        $this->lastProcessedDate = now();
        $this->processed = 0;
    }

    public function handle()
    {
        $this->debug = $this->argument('debug');
        $this->limit = (int) $this->argument('limit');

        $this->processPage();
        $this->info("Saved articles: ".$this->processed);
    }

    function getUrl(string $url) : string
    {
        if (file_exists(self::IMPERSONATE_COMMAND)) {
            return $this->getImpersonate($url);
        } else {
            return $this->getRegular($url);
        }
    }

    function getImpersonate($url) {
        // curl-impersonate is used to bypass cloudflare bot protection
        // https://github.com/lwthiker/curl-impersonate
        return shell_exec(self::IMPERSONATE_COMMAND . ' ' . escapeshellarg($url));
    }

    function getRegular($url) {
        $resp = Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Accept-Language' => 'uk',
            'Cache-Control' => 'no-cache',
            'DNT' => '1',
            'Pragma' => 'no-cache',
            'Priority' => 'u=0, i',
            'Sec-CH-UA' => '"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"macOS"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
        ])->get(self::BLOG_URL);
        if (strpos($resp, '<title>Just a moment...</title>')) {
            $this->error('You got CloudFlare bot protection page. You should manually download "curl-impersonate" and setup IMPERSONATE_COMMAND const https://github.com/lwthiker/curl-impersonate/releases');
            die;
        }
        return $resp;
    }

    function getIndexHtml($page = 1)
    {
        $params = '';
        if ($page > 1) {
            $params .= '?page='.$page;
        }
        return $this->getUrl(self::BLOG_URL.$params);
    }

    function findLinks($body)
    {
        $crawler = new Crawler($body);
        $links = $crawler->filterXPath("//a[span[@class='sr-only' and text()='Read article']]")->extract(['href']);
        return $links;
    }

    function findArticleData($html) : array
    {
        $articleCrawler = new Crawler($html);

        $title = $articleCrawler->filter('article h1')->first()->text('');
        $dateString = $articleCrawler->filter('article time')->first()->attr('datetime');
        if ($dateString) {
            try {
                $date = Carbon::parse($dateString); // Parse the date string
            } catch (\Exception $e) {
                $this->error("Invalid date format: $dateString");
                return [];
            }
        } else {
            $this->error("Missing date in the article.");
            return [];
        }
        $author = $articleCrawler->filter('article time + a')->first()->text('');
        $content = $articleCrawler->filterXPath('//article/div[2]/div[1]')->first()->html('');

        if ($this->debug) {
            $this->info("Title: ".$title??'');
            $this->info("Date: ".$date->toDateString()??'');
            $this->info("Author: ".$author??'');
        }

        if (!$title || !$author) {
            $this->error("Missing article data.");
            $this->line($html);
            return [];
        }
        return [
            'title' => $title,
            'date' => $date,
            'author' => $author,
            'content' => $content,
        ];
    }

    function processPage($page = 1)
    {
        $response = $this->getIndexHtml($page);
        if (!$response) {
            $this->error("Failed to fetch content from the URL.");
            return;
        }

        $links = $this->findLinks($response);

        if (!$links) {
            $this->error("No article URLs found.");
            $this->line($response);
            return;
        }
        if ($this->debug) {
            foreach ($links as $link) {
                $this->info($link);
            }
        }

        if ($this->limit === 0) return;

        $success = false;
        foreach ($links as $link) {
            $success = false;
            if ($this->processed >= $this->limit) {
                break;
            }
            $savedArticle = $this->articleRepository->getArticleByUrl($link);
            if ($savedArticle) {
                $this->error("We already have this article: ".$link);
                break;
            }

            $articleResponse = $this->getUrl($link);

            if (!$articleResponse) {
                $this->error("Failed to fetch article content from $link.");
                break;
            }

            $article = $this->findArticleData($articleResponse);

            if (!$article) {
                $this->error("Failed find article data");
                break;
            }

            if ($this->cutoffDate > $article['date']) {
                break;
            }

            $article['url'] = $link;
            Article::create($article);
            $this->processed++;
            $success = true;
        }

        if ($success) {
            $this->processPage($page+1);
        }
    }
}
