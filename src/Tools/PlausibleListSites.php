<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleListSites implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'List websites tracked in Plausible Analytics. Returns site domains you can query for analytics data.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            // Try the Sites API first (available on Plausible Cloud / Enterprise)
            try {
                $limit = isset($request['limit']) ? (int) $request['limit'] : 100;
                $result = $this->service->listSites($limit, $request['after'] ?? null);
                return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } catch (\RuntimeException $e) {
                // Sites API not available — fall back to configured sites
            }

            // Return sites from configuration
            $configuredSites = $this->service->getConfiguredSites();
            if (!empty($configuredSites)) {
                $sites = array_map(fn (string $domain) => ['domain' => $domain], $configuredSites);
                return json_encode([
                    'sites' => $sites,
                    'source' => 'configured',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            return 'No sites found. The Sites API is not available on this Plausible instance, and no sites have been configured. Ask the workspace admin to add site domains in the Plausible integration settings.';
        } catch (\Throwable $e) {
            return "Error listing sites: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema
                ->integer()
                ->description('Maximum number of sites to return (default: 100).'),
            'after' => $schema
                ->string()
                ->description('Cursor for pagination — pass the value from a previous response to get the next page.'),
        ];
    }
}
