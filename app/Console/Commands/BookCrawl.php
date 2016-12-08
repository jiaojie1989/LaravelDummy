<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Cookie\CookieJar;
use App\Models\Foxebook\Book;
use GuzzleHttp\RequestOptions;
use Carbon\Carbon;
use Malenki\Ansi;

class BookCrawl extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl:foxebook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取foxebook的书籍';

    /**
     * GuzzleHttp Client
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    const FOXEBOOK_NEW_RELEASE_URI = "/new-release/";
    const FOXEBOOK_BASE_URL = "http://www.foxebook.net";

    protected static $cookieJar = null;
    protected static $options = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setOptions();
        $this->initHttpClient();
    }

    protected function setOptions()
    {
        if (empty(static::$options)) {
            static::$cookieJar = new CookieJar();
            static::$options = [
                RequestOptions::HEADERS => [
                    "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0",
                ],
                RequestOptions::COOKIES => static::$cookieJar,
                RequestOptions::CONNECT_TIMEOUT => 10,
                RequestOptions::PROXY => [
                    "http" => "tcp://10.235.52.40:3128",
                    "https" => "tcp://10.235.52.40:3128",
                ],
            ];
        }
    }

    protected function printTime()
    {
        echo (new Ansi("[" . Carbon::now()->toDateTimeString() . "]"))->fg('white')->bg('magenta')->bold();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resp = $this->httpClient->get(self::FOXEBOOK_BASE_URL . self::FOXEBOOK_NEW_RELEASE_URI, static::$options);
        $content = strval($resp->getBody());
        $this->filterBooks($content);
        $url = $this->nextPageUrl($content);

        while ($url) {
            $resp = $this->httpClient->get($url, static::$options);
            $content = strval($resp->getBody());
            $this->filterBooks($content);
            $url = $this->nextPageUrl($content);
        }
    }

    protected function nextPageUrl($pageContent)
    {
        $crawler = new Crawler($pageContent);
        $resps = $crawler->filter(".pagination  > li > a")
                ->each(function(Crawler $node, $i) {
            if (false !== mb_stristr($node->text(), "next")) {
                return self::FOXEBOOK_BASE_URL . $node->attr("href");
            } else {
                return true;
            }
        });
        foreach ($resps as $resp) {
            if ($resp !== true) {
                return $resp;
            }
        }
        return false;
    }

    protected function crawlABook($title, $url, Book $book)
    {
        if (false === $book->crawled) {
            $resp = $this->httpClient->get($url, static::$options);
            $content = strval($resp->getBody());
            $this->filterBookInfo($content, $book);
            $this->filterBookBasicInfo($content, $book);
            $book->originHtml = $content;
            $book->crawled = true;
            $book->save();
            $this->printTime();
            echo Ansi::parse("<blue>[crawl success]</blue>") . ("'{$title}' '{$url}'\n");
        } else {
            $this->printTime();
            echo Ansi::parse("<purple>[already crawled]</purple>") . ("'{$title}' '{$url}'\n");
        }
    }

    protected function filterBookInfo($content, Book &$book)
    {
        $crawler = new Crawler($content);
        $crawler->filter(".panel-primary > .panel-body")->each(function(Crawler $node, $i) use($book) {
            try {
                switch ($i) {
                    case 0:
                        $book->description = trim(strip_tags($node->html()));
                        break;
                    case 1:
                        $book->detail = trim(strip_tags($node->html()));
                        break;
                    case 2:
                        $book->download = trim($node->html());
                        break;
                    default:
                }
            } catch (\Exception $e) {
                
            }
        });
    }

    protected function filterBookBasicInfo($content, Book &$book)
    {
        $crawler = new Crawler($content);
        $crawler = $crawler->filter(".info > i");
        $crawler->each(function(Crawler $node, $i) use($book, $crawler) {
            if ($crawler->count() > 3) {
                try {
                    switch ($i) {
                        case 0:
                            $book->auther = $node->filter("a")->text();
                            break;
                        case 1:
                            $book->publisher = $node->text();
                            break;
                        case 2:
                            $book->publishDate = $node->text();
                            break;
                        case 3:
                            $book->pages = $node->text();
                            break;
                        default:
                    }
                } catch (\Exception $e) {
                    
                }
            } else {
                $this->printTime();
                echo Ansi::parse("<bold><yellow>[Error]</yellow></bold>") . ("'{$book->title}' '{$book->url}'\n");
            }
        });
    }

    protected function filterBooks($pageContent)
    {
        $crawler = new Crawler($pageContent);
        $crawler->filter(".book-top")->each(function(Crawler $node, $i) {
            $arr = $this->filterBook($node->html());
            if (!empty($arr)) {
                $title = $arr["title"];
                $url = $arr["url"];
                if (null === ($book = Book::find(md5($title)))) {
                    $book = new Book;
                    $book->_id = md5($title);
                    $book->title = $title;
                    $book->url = $url;
                    $book->crawled = false;
                    $book->save();
                    $this->printTime();
                    echo Ansi::parse("<green>[new to mongo]</green>") . ("'{$arr["title"]}'\n");
                }
                $book->url = $url;
                $this->crawlABook($title, $url, $book);
            }
        });
    }

    protected function filterBook($html)
    {
        $crawler = new Crawler($html);
        $crawler = $crawler->filter("h3 > a");
        if ($crawler->count() > 0) {
            return [
                "title" => $crawler->attr("title"),
                "url" => self::FOXEBOOK_BASE_URL . $crawler->attr("href"),
            ];
        } else {
            return false;
        }
    }

    protected function initHttpClient()
    {
        $this->httpClient = new Client;
    }

    protected function setBookQueue($content)
    {
        
    }

}
