<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleRealtimeVisitors implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Get the current number of realtime visitors on a website (visitors in the last 5 minutes).';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $count = $this->service->realtimeVisitors($request['site_id']);

            return json_encode([
                'site_id' => $request['site_id'],
                'realtime_visitors' => $count,
            ], JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            return "Error getting realtime visitors: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'site_id' => $schema
                ->string()
                ->description('The site domain (e.g., "example.com").')
                ->required(),
        ];
    }
}
