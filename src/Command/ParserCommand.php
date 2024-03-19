<?php

namespace App\Command;

use App\Util\ProxyRandomizer;
use App\Util\RequestAttempt;
use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;


class ParserCommand extends Command
{
    use ProxyRandomizer, RequestAttempt;
    private const ERROR_PATTERN = "%s}##{%s\n";
    private const RESULT_PATTERN = "%s}##{%s}##{%s}##{%s}##{%s}##{%s}##{%s}##{%s\n";


    protected function configure(): void
    {
        // Use in-build functions to set name, description and help
        $this->setName('parse:financy.bg')
            ->setDescription('This command runs parsing financy.bg')
            ->setHelp('Run this command to execute your custom tasks in the execute function.');
    }

    public static function register(Application $app)
    {
        $app->add(new self());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new Client([
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
                'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:123.0) Gecko/20100101 Firefox/123.0'
            ]
        ]);

        $xmlUrl = 'https://finansi.bg/sitemaps/5.xml';

        $response = $client->get($xmlUrl);

        $xml = $response->getBody()->getContents();

        $crawler = new Crawler($xml);

        $crawler->registerNamespace('ns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $urls = $crawler->filter('ns|url');

        $parsedUrls = [];

        $urls -> each(function (Crawler $url) use (&$parsedUrls){
            $loc = $url->filter('ns | loc') -> text();
            $lastmod = $url->filter('ns|lastmod')->text();
            $parsedUrls[] = [
                'url' => $loc,
                'lastmod' => $lastmod,
            ];
            dd($parsedUrls);
            return $parsedUrls;
        });

        $this->writeUrlsToFile($parsedUrls, "output.csv");

        return Command::SUCCESS;
    }

    function writeUrlsToFile($parsedUrls, $filename) {
        $csvFile = fopen($filename, 'w');
        foreach ($parsedUrls as $parsedUrl) {
            fputcsv($csvFile, $parsedUrl);
        }
        fclose($csvFile);

    }

    function getSitemaps($xmlUrl): array
    {
        $client = new Client();
        $response = $client->get($xmlUrl);
        $xml = $response->getBody()->getContents();

        $domCrawler = new Crawler($xml);
        $sitemaps = $domCrawler->filter('loc');

        $sitemapUrls = [];
        $sitemaps->each(function (Crawler $sitemap) use (&$sitemapUrls) {
            $sitemapUrls[] = $sitemap->text();
        });
        return $sitemapUrls;
    }

    function getFirstFiveRawPages($xmlUrl): array
    {
        $client = new Client();
        $response = $client->get($xmlUrl);
        $xml = $response->getBody()->getContents();

        $domCrawler = new Crawler($xml);
        $sitemaps = $domCrawler->filter('loc');

        $sitemapUrls = [];
        $sitemaps->each(function (Crawler $sitemap) use (&$sitemapUrls) {
            $sitemapUrls[] = $sitemap->text();
        });

        return array_slice($sitemapUrls, 1, 4);
    }
}