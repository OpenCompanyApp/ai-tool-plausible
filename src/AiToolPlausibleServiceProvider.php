<?php

namespace OpenCompany\AiToolPlausible;

use Illuminate\Support\ServiceProvider;
use OpenCompany\AiToolCore\Contracts\CredentialResolver;
use OpenCompany\AiToolCore\Support\ToolProviderRegistry;

class AiToolPlausibleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlausibleService::class, function ($app) {
            $creds = $app->make(CredentialResolver::class);

            return new PlausibleService(
                apiKey: $creds->get('plausible', 'api_key', ''),
                baseUrl: $creds->get('plausible', 'url', 'https://plausible.io'),
                sites: $creds->get('plausible', 'sites', []) ?? [],
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->bound(ToolProviderRegistry::class)) {
            $this->app->make(ToolProviderRegistry::class)
                ->register(new PlausibleToolProvider());
        }
    }
}
