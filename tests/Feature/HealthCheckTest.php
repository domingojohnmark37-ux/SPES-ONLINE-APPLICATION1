<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthz_returns_success_without_web_session_middleware(): void
    {
        $this->get('/healthz')->assertOk()->assertSeeText('OK');

        $route = Route::getRoutes()->match(Request::create('/healthz'));

        $this->assertNotContains('web', $route->gatherMiddleware());
        $this->assertNotContains(\Illuminate\Session\Middleware\StartSession::class, $route->gatherMiddleware());
    }
}
