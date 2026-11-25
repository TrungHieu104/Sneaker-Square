<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Behind a tunnel the browser speaks https to the tunnel and the tunnel speaks
 * http to the app; only the forwarded headers carry the truth.
 */
class TrustedProxiesTest extends TestCase
{
    private const LOCAL_SITE = 'http://sneaker-square.test';

    private const TUNNEL_HOST = 'demo.trycloudflare.com';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_proxy-probe', fn () => asset('frontend/css/main.css'));
    }

    protected function tearDown(): void
    {
        // Symfony keeps the trusted proxies in a static, so they would leak
        // into the next test.
        Request::setTrustedProxies([], -1);

        parent::tearDown();
    }

    public function test_khong_khai_bao_proxy_thi_bo_qua_header_cua_tunnel(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->throughTunnel()->assertSeeText(self::LOCAL_SITE.'/frontend/css/main.css');
    }

    public function test_tin_moi_proxy_thi_link_theo_https_va_ten_mien_tunnel(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->throughTunnel()->assertSeeText('https://'.self::TUNNEL_HOST.'/frontend/css/main.css');
    }

    public function test_proxy_goi_toi_khong_nam_trong_danh_sach_thi_bo_qua_header(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1, 10.0.0.2']);

        $this->throughTunnel()->assertSeeText(self::LOCAL_SITE.'/frontend/css/main.css');
    }

    private function throughTunnel(): TestResponse
    {
        return $this->get(self::LOCAL_SITE.'/_proxy-probe', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => self::TUNNEL_HOST,
            'X-Forwarded-Port' => '443',
        ]);
    }
}
