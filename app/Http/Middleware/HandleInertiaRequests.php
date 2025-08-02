<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\URL;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        return array_merge(parent::share($request), [
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'error' => fn() => $request->session()->get('error'),
                'pending_message' => fn() => $request->session()->get('pending_message'),
            ],
            'app' => [
                'url' => config('app.url'),
            ],
        ]);
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        if (config('app.env') === 'production' && $request->inertia()) {
            URL::forceScheme('https');
        }

        return parent::handle($request, $next);
    }
}
