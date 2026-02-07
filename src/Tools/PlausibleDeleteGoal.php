<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleDeleteGoal implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Delete a conversion goal from a website in Plausible.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $this->service->deleteGoal($request['siteId'], (int) $request['goalId']);

            return "Goal {$request['goalId']} has been deleted from site '{$request['siteId']}'.";
        } catch (\Throwable $e) {
            return "Error deleting goal: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'siteId' => $schema
                ->string()
                ->description('The site domain (e.g., "example.com").')
                ->required(),
            'goalId' => $schema
                ->integer()
                ->description('The ID of the goal to delete.')
                ->required(),
        ];
    }
}
