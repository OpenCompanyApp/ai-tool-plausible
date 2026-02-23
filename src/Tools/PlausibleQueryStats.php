<?php

namespace OpenCompany\AiToolPlausible\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolPlausible\PlausibleService;

class PlausibleQueryStats implements Tool
{
    public function __construct(
        private PlausibleService $service,
    ) {}

    public function description(): string
    {
        return 'Query website analytics from Plausible. Supports aggregate stats, timeseries, and breakdowns by dimension. Use dimensions to group results (e.g., by country, source, page). Omit dimensions for simple aggregate totals.';
    }

    public function handle(Request $request): string
    {
        try {
            if (!$this->service->isConfigured()) {
                return 'Error: Plausible integration is not configured.';
            }

            $body = [
                'site_id' => $request['siteId'],
                'metrics' => $request['metrics'],
                'date_range' => $request['dateRange'],
            ];

            if (isset($request['dimensions'])) {
                $body['dimensions'] = $request['dimensions'];
            }

            if (isset($request['filters'])) {
                $filters = $request['filters'];
                $body['filters'] = is_string($filters) ? json_decode($filters, true) : $filters;
            }

            if ($request['dateRange'] === 'custom') {
                if (isset($request['dateFrom']) && isset($request['dateTo'])) {
                    $body['date_range'] = [$request['dateFrom'], $request['dateTo']];
                } else {
                    return 'Error: dateFrom and dateTo are required when dateRange is "custom".';
                }
            }

            if (isset($request['orderBy'])) {
                $orderBy = $request['orderBy'];
                $body['order_by'] = is_string($orderBy) ? json_decode($orderBy, true) : $orderBy;
            }

            if (isset($request['limit'])) {
                $body['pagination'] = ['limit' => (int) $request['limit']];
            }

            $result = $this->service->query($body);

            return $this->formatResponse($result);
        } catch (\Throwable $e) {
            return "Error querying Plausible stats: {$e->getMessage()}";
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatResponse(array $result): string
    {
        $query = $result['query'] ?? [];
        $metricNames = $query['metrics'] ?? [];
        $dimensionNames = $query['dimensions'] ?? [];
        $results = $result['results'] ?? [];
        $meta = $result['meta'] ?? [];

        $rows = array_map(function (array $row) use ($metricNames, $dimensionNames) {
            $entry = [];
            foreach ($dimensionNames as $i => $dim) {
                $entry[$dim] = $row['dimensions'][$i] ?? null;
            }
            foreach ($metricNames as $i => $metric) {
                $val = $row['metrics'][$i] ?? null;
                if (is_array($val)) {
                    $entry[$metric] = $val;
                } elseif (is_numeric($val)) {
                    $entry[$metric] = str_contains((string) $val, '.') ? (float) $val : (int) $val;
                } else {
                    $entry[$metric] = $val;
                }
            }

            return $entry;
        }, $results);

        $response = [];

        if (isset($query['date_range'])) {
            $response['dateRange'] = $query['date_range'];
        }
        if (! empty($dimensionNames)) {
            $response['dimensions'] = $dimensionNames;
        }
        $response['metrics'] = $metricNames;
        $response['rows'] = $rows;
        $response['rowCount'] = count($rows);

        if (isset($meta['total_rows'])) {
            $response['totalRows'] = $meta['total_rows'];
        }

        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'siteId' => $schema
                ->string()
                ->description('The site domain (e.g., "example.com").')
                ->required(),
            'metrics' => $schema
                ->array()
                ->items($schema->string())
                ->description('Metrics to retrieve: visitors, pageviews, visits, bounce_rate, visit_duration, views_per_visit, events, conversion_rate.')
                ->required(),
            'dateRange' => $schema
                ->string()
                ->description('Time period: "7d", "28d", "30d", "month", "3mo", "6mo", "12mo", or "custom" (requires dateFrom/dateTo).')
                ->required(),
            'dimensions' => $schema
                ->array()
                ->items($schema->string())
                ->description('Dimensions to group by: visit:source, visit:country, visit:city, visit:device, visit:browser, visit:os, event:page, event:name, time:day, time:month, etc.'),
            'filters' => $schema
                ->string()
                ->description('JSON-encoded filter expressions, e.g., [["is", "visit:country", ["NL"]]]. Pass as a JSON string.'),
            'dateFrom' => $schema
                ->string()
                ->description('Start date (ISO 8601, e.g., "2025-01-01") when dateRange is "custom".'),
            'dateTo' => $schema
                ->string()
                ->description('End date (ISO 8601, e.g., "2025-01-31") when dateRange is "custom".'),
            'orderBy' => $schema
                ->string()
                ->description('JSON-encoded order, e.g., [["visitors", "desc"]]. Pass as a JSON string.'),
            'limit' => $schema
                ->integer()
                ->description('Maximum number of results to return. Sent as pagination.limit (default: 10000).'),
        ];
    }
}
