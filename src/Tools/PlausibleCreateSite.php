<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleCreateSite implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Register a new website for tracking in Plausible Analytics.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $timezone = $request['timezone'] ?? 'Etc/UTC';
            $result = $this->service->createSite($request['domain'], $timezone);

            return "Site '{$request['domain']}' created successfully.\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error creating site: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'domain' => $schema
                ->string()
                ->description('The domain to track (e.g., "example.com").')
                ->required(),
            'timezone' => $schema
                ->string()
                ->description('Timezone for the site (e.g., "Europe/Amsterdam"). Defaults to "Etc/UTC".'),
        ];
    }
}
