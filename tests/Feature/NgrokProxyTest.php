<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NgrokProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_assets_have_https_safe_relative_paths(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="/assets/style.css?v=1.2.0"', false)
            ->assertSee('src="/assets/app.js?v=1.2.0"', false);
    }

    public function test_bootstrap_configuration_does_not_call_config_while_container_is_unready(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        // Original v1.1.1 regression: a config() call in ->withMiddleware()
        // throws "Target class [config] does not exist" during artisan bootstrap.
        $this->assertStringNotContainsString("$"."trustedProxies = trim((string) config(", $bootstrap);
        $this->assertFileExists(base_path('config/trustedproxy.php'));
        $this->assertNotEmpty(config('trustedproxy.proxies'));
    }

    public function test_ngrok_owner_session_does_not_redirect_to_public_home(): void
    {
        $owner = \App\Models\User::query()->create([
            'name' => 'Owner Proxy',
            'email' => 'owner-proxy@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('test-password-secure'),
        ]);

        $this->actingAs($owner)->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'serenity-test.ngrok-free.dev',
            'SERVER_PORT' => '80',
            'HTTPS' => 'off',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'serenity-test.ngrok-free.dev',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('/secretkya')
            ->assertRedirect(route('owner.dashboard'));
    }

    public function test_local_ngrok_proxy_generates_https_routes_from_forwarded_headers(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'serenity-test.ngrok-free.dev',
            'SERVER_PORT' => '8080',
            'HTTPS' => 'off',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'serenity-test.ngrok-free.dev',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('/')
            ->assertOk()
            ->assertDontSee('Owner Login')->assertDontSee('secretkya');
    }
}
