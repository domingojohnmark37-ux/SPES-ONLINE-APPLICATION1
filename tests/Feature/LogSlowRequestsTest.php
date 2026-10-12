<?php

namespace Tests\Feature;

use App\Http\Middleware\LogSlowRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogSlowRequestsTest extends TestCase
{
    public function test_slow_requests_log_the_route_duration_and_peak_memory(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Slow HTTP request.', \Mockery::on(
                fn (array $context): bool => $context['route'] === 'test.slow'
                    && $context['duration_ms'] >= 3000
                    && $context['memory_bytes'] > 0,
            ));

        $request = Request::create('/slow', 'GET');
        $route = new Route('GET', '/slow', static fn () => null);
        $route->name('test.slow');
        $request->setRouteResolver(static fn () => $route);

        $response = app(LogSlowRequests::class)->handle($request, static function () {
            usleep(3_010_000);

            return response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }
}
