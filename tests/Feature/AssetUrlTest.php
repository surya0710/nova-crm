<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AssetUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_serve_uses_standard_build_path(): void
    {
        Config::set('app.url', 'http://127.0.0.1:8000');
        Config::set('app.asset_url', null);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('/build/assets/', false);
        $response->assertDontSee('/nova-crm/public/build/assets/', false);
    }

    public function test_xampp_subdirectory_uses_prefixed_build_path(): void
    {
        Config::set('app.url', 'http://localhost/nova-crm/public');
        Config::set('app.asset_url', 'http://localhost/nova-crm/public');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('http://localhost/nova-crm/public/build/assets/', false);
    }
}
