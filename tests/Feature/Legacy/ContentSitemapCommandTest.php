<?php

namespace Tests\Feature\Legacy;

use Illuminate\Support\Facades\Http;
use Spatie\Crawler\Crawler;
use Spatie\Sitemap\SitemapGenerator;
use Spatie\Sitemap\Tags\Url;
use Tests\TestCase;

/**
 * Characterisation of `app:generate-sitemap`. The real generator crawls the
 * site over HTTP and writes public/sitemap.xml, so a recording stand-in takes
 * its place and nothing is fetched or written.
 */
class ContentSitemapCommandTest extends TestCase
{
    public function test_lenh_tao_sitemap_quet_app_url_va_ghi_vao_public(): void
    {
        Http::preventStrayRequests();
        config(['app.url' => 'https://shop.example.test']);

        $generator = new class(Crawler::create()) extends SitemapGenerator
        {
            public ?string $writtenTo = null;

            public function writeToFile(string $path): static
            {
                $this->writtenTo = $path;

                return $this;
            }

            public function crawledUrl(): string
            {
                return (string) $this->urlToBeCrawled;
            }

            public function onCrawled(Url $url): mixed
            {
                return ($this->hasCrawled)($url);
            }
        };
        $this->app->instance(SitemapGenerator::class, $generator);

        $this->artisan('app:generate-sitemap')->assertSuccessful();

        $this->assertSame('https://shop.example.test/', $generator->crawledUrl());
        $this->assertSame(public_path('sitemap.xml'), $generator->writtenTo);

        $url = $generator->onCrawled(Url::create('https://shop.example.test/bai-viet'));
        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame(Url::CHANGE_FREQUENCY_WEEKLY, $url->changeFrequency);
    }
}
