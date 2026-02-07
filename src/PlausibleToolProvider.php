<?php

namespace OpenCompany\AiToolPlausible;

use Laravel\Ai\Contracts\Tool;
use OpenCompany\AiToolCore\Contracts\ToolProvider;
use OpenCompany\AiToolPlausible\Tools\PlausibleCreateGoal;
use OpenCompany\AiToolPlausible\Tools\PlausibleCreateSite;
use OpenCompany\AiToolPlausible\Tools\PlausibleDeleteGoal;
use OpenCompany\AiToolPlausible\Tools\PlausibleDeleteSite;
use OpenCompany\AiToolPlausible\Tools\PlausibleListGoals;
use OpenCompany\AiToolPlausible\Tools\PlausibleListSites;
use OpenCompany\AiToolPlausible\Tools\PlausibleQueryStats;
use OpenCompany\AiToolPlausible\Tools\PlausibleRealtimeVisitors;

class PlausibleToolProvider implements ToolProvider
{
    public function appName(): string
    {
        return 'plausible';
    }

    public function appMeta(): array
    {
        return [
            'label' => 'query, realtime, sites, goals',
            'description' => 'Website analytics',
            'icon' => 'ph:chart-line-up',
            'logo' => 'simple-icons:plausibleanalytics',
        ];
    }

    public function tools(): array
    {
        return [
            'plausible_query_stats' => [
                'class' => PlausibleQueryStats::class,
                'type' => 'read',
                'name' => 'Query Stats',
                'description' => 'Query website analytics (aggregate, timeseries, breakdowns).',
                'icon' => 'ph:chart-line-up',
            ],
            'plausible_realtime_visitors' => [
                'class' => PlausibleRealtimeVisitors::class,
                'type' => 'read',
                'name' => 'Realtime Visitors',
                'description' => 'Get current realtime visitor count.',
                'icon' => 'ph:users',
            ],
            'plausible_list_sites' => [
                'class' => PlausibleListSites::class,
                'type' => 'read',
                'name' => 'List Sites',
                'description' => 'List all tracked websites.',
                'icon' => 'ph:globe',
            ],
            'plausible_create_site' => [
                'class' => PlausibleCreateSite::class,
                'type' => 'write',
                'name' => 'Create Site',
                'description' => 'Register a new website for tracking.',
                'icon' => 'ph:globe',
            ],
            'plausible_delete_site' => [
                'class' => PlausibleDeleteSite::class,
                'type' => 'write',
                'name' => 'Delete Site',
                'description' => 'Remove a website from tracking.',
                'icon' => 'ph:trash',
            ],
            'plausible_list_goals' => [
                'class' => PlausibleListGoals::class,
                'type' => 'read',
                'name' => 'List Goals',
                'description' => 'List conversion goals for a site.',
                'icon' => 'ph:target',
            ],
            'plausible_create_goal' => [
                'class' => PlausibleCreateGoal::class,
                'type' => 'write',
                'name' => 'Create Goal',
                'description' => 'Create a conversion goal (page or event).',
                'icon' => 'ph:target',
            ],
            'plausible_delete_goal' => [
                'class' => PlausibleDeleteGoal::class,
                'type' => 'write',
                'name' => 'Delete Goal',
                'description' => 'Delete a conversion goal.',
                'icon' => 'ph:trash',
            ],
        ];
    }

    public function isIntegration(): bool
    {
        return true;
    }

    public function createTool(string $class, array $context = []): Tool
    {
        $service = app(PlausibleService::class);

        return new $class($service);
    }
}
