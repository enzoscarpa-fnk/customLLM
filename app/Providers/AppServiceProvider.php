<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        URL::forceScheme("https");
        URL::forceRootUrl("https://customllm.studiofnk.be");

        Inertia::share([
            "app" => [
                "url" => "https://customllm.studiofnk.be",
            ],
        ]);
    }
}
