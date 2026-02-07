<?php

namespace OpenCompany\AiToolPlausible;

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use OpenCompany\AiToolCore\Contracts\ConfigurableIntegration;
use OpenCompany\AiToolCore\Contracts\ToolProvider;
use OpenCompany\AiToolPlausible\Tools\PlausibleCreateGoal;
use OpenCompany\AiToolPlausible\Tools\PlausibleCreateSite;
use OpenCompany\AiToolPlausible\Tools\PlausibleDeleteGoal;
use OpenCompany\AiToolPlausible\Tools\PlausibleDeleteSite;
use OpenCompany\AiToolPlausible\Tools\PlausibleListGoals;
use OpenCompany\AiToolPlausible\Tools\PlausibleListSites;
use OpenCompany\AiToolPlausible\Tools\PlausibleQueryStats;
use OpenCompany\AiToolPlausible\Tools\PlausibleRealtimeVisitors;

class PlausibleToolProvider implements ToolProvider, ConfigurableIntegration
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

    public function integrationMeta(): array
    {
        return [
            'name' => 'Plausible Analytics',
            'description' => 'Privacy-friendly website analytics',
            'icon' => 'ph:chart-line-up',
            'logo' => 'simple-icons:plausibleanalytics',
            'category' => 'analytics',
            'badge' => 'verified',
            'docs_url' => 'https://plausible.io/docs/stats-api',
        ];
    }

    public function configSchema(): array
    {
        return [
            [
                'key' => 'api_key',
                'type' => 'secret',
                'label' => 'API Key',
                'placeholder' => 'Enter your Plausible API key',
                'hint' => 'Generate an API key in your Plausible account settings under "API Keys"',
                'required' => true,
            ],
            [
                'key' => 'url',
                'type' => 'url',
                'label' => 'Instance URL',
                'placeholder' => 'https://plausible.io',
                'hint' => 'Use <code>https://plausible.io</code> for cloud, or your self-hosted URL',
                'default' => 'https://plausible.io',
            ],
            [
                'key' => 'sites',
                'type' => 'string_list',
                'label' => 'Tracked Sites',
                'hint' => 'Add the domains you track in Plausible (e.g., <code>example.com</code>). Agents use these to query analytics.',
                'default' => [],
                'item_icon' => 'ph:globe',
                'item_placeholder' => 'example.com',
            ],
        ];
    }

    public function testConnection(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        $baseUrl = rtrim($config['url'] ?? 'https://plausible.io', '/');

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No API key provided'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($baseUrl.'/api/v2/query', [
                'site_id' => '__connection_test__',
                'metrics' => ['visitors'],
                'date_range' => '7d',
            ]);

            $json = $response->json();

            if ($json === null) {
                return [
                    'success' => false,
                    'error' => "Could not reach Plausible API at {$baseUrl}. Check the URL.",
                ];
            }

            return [
                'success' => true,
                'message' => "Connected to Plausible API at {$baseUrl}.",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function validationRules(): array
    {
        return [
            'api_key' => 'nullable|string',
            'url' => 'nullable|url',
            'sites' => 'nullable|array',
            'sites.*' => 'string',
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
