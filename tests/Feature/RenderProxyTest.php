<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RenderProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/deployment-proxy', fn (Request $request) => [
            'url' => url('/login/student'),
            'asset' => asset('css/responsive.css'),
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
        ]);
    }

    public function test_render_proxy_keeps_links_https_without_trusting_forwarded_host(): void
    {
        config(['app.trust_render_proxy' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-For' => '203.0.113.10',
                'X-Forwarded-Host' => 'untrusted.example',
            ])
            ->get('http://scholarlink.onrender.com/_test/deployment-proxy')
            ->assertOk()
            ->assertJsonPath('url', 'https://scholarlink.onrender.com/login/student')
            ->assertJsonPath('asset', 'https://scholarlink.onrender.com/css/responsive.css')
            ->assertJsonPath('ip', '203.0.113.10')
            ->assertJsonPath('secure', true);
    }

    public function test_local_installation_does_not_trust_forwarded_headers_by_default(): void
    {
        config(['app.trust_render_proxy' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-For' => '203.0.113.10',
            ])
            ->get('http://localhost/_test/deployment-proxy')
            ->assertOk()
            ->assertJsonPath('url', 'http://localhost/login/student')
            ->assertJsonPath('asset', 'http://localhost/css/responsive.css')
            ->assertJsonPath('ip', '10.0.0.5')
            ->assertJsonPath('secure', false);
    }
}
