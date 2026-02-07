<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleCreateGoal implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Create a conversion goal for a website in Plausible. Goals can track pageviews to specific pages or custom events.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $goal = [];
            if (isset($request['eventName'])) {
                $goal['goal_type'] = 'event';
                $goal['event_name'] = $request['eventName'];
            } elseif (isset($request['pagePath'])) {
                $goal['goal_type'] = 'page';
                $goal['page_path'] = $request['pagePath'];
            } else {
                return 'Error: Either eventName or pagePath is required.';
            }

            $result = $this->service->createGoal($request['siteId'], $goal);

            return "Goal created successfully.\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error creating goal: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'siteId' => $schema
                ->string()
                ->description('The site domain (e.g., "example.com").')
                ->required(),
            'eventName' => $schema
                ->string()
                ->description('Custom event name to track (e.g., "Signup"). Use this OR pagePath, not both.'),
            'pagePath' => $schema
                ->string()
                ->description('Page path to track visits to (e.g., "/thank-you"). Use this OR eventName, not both.'),
        ];
    }
}
