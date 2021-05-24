<?php

namespace Butler\Auth\Tests;

use Butler\Auth\ButlerAuth;
use Butler\Auth\ServiceProvider;
use Butler\Auth\Tests\Models\ConsumerWithTokenSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ActingAsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    public function test_actingAs_on_route_protected_by_middleware()
    {
        Route::get('/foo', fn () => 'bar')->middleware('auth:butler');

        $consumer = tap(new ConsumerWithTokenSupport())->setAttribute('id', 1);

        ButlerAuth::actingAs($consumer);

        $this->get('/foo')->assertOk()->assertSee('bar');
    }

    public function test_actingAs_on_route_protected_by_ability()
    {
        Route::get('/foo', fn () => Auth::user()->tokenCan('baz') ? 'bar' : abort(403))
            ->middleware('auth:butler');

        $consumer = tap(new ConsumerWithTokenSupport())->setAttribute('id', 1);

        ButlerAuth::actingAs($consumer, ['baz']);

        $this->get('/foo')->assertOk()->assertSee('bar');
    }

    public function test_actingAs_when_key_has_any_ability()
    {
        Route::get('/foo', fn () => Auth::user()->tokenCan('baz') ? 'bar' : abort(403))
            ->middleware('auth:butler');

        $consumer = tap(new ConsumerWithTokenSupport())->setAttribute('id', 1);

        ButlerAuth::actingAs($consumer, ['*']);

        $this->get('/foo')->assertOk()->assertSee('bar');
    }
}
