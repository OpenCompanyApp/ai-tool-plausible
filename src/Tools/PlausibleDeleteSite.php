<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleDeleteSite implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Remove a website from Plausible Analytics tracking. This deletes all associated data.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $this->service->deleteSite($request['siteId']);

            return "Site '{$request['siteId']}' has been deleted. Data removal may take up to 48 hours.";
        } catch (\Throwable $e) {
            return "Error deleting site: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'siteId' => $schema
                ->string()
                ->description('The site domain to delete (e.g., "example.com").')
                ->required(),
        ];
    }
}
