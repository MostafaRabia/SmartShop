<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected string $endpoint;

    protected int $timeout;

    protected int $catalogTtl;

    public function __construct()
    {
        /** @var string $apiKey */
        $apiKey = config('gemini.api_key', '');
        /** @var string $model */
        $model = config('gemini.model', '');
        /** @var string $endpoint */
        $endpoint = config('gemini.endpoint', '');
        $timeoutConfig = config('gemini.timeout', 8);
        $timeout = is_numeric($timeoutConfig) ? (int) $timeoutConfig : 8;

        $catalogConfig = config('gemini.catalog_cache_seconds', 120);
        $catalogTtl = is_numeric($catalogConfig) ? (int) $catalogConfig : 120;

        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->endpoint = rtrim($endpoint, '/');
        $this->timeout = $timeout;
        $this->catalogTtl = $catalogTtl;
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->model);
    }

    /**
     * Get a cached projection of the product catalog for prompting.
     *
     * @return array<int, array{id:int,name:string,description:string,price:float}>
     */
    public function getCatalogProjection(): array
    {
        /** @var array<int, array{id:int,name:string,description:string,price:float}> $catalog */
        $catalog = Cache::remember('gemini:catalog_projection', $this->catalogTtl, function (): array {
            return Product::query()
                ->select('id', 'name', 'description', 'price')
                ->orderBy('id')
                ->get()
                ->map(fn ($p) => [
                    'id' => (int) $p->id,
                    'name' => (string) $p->name,
                    'description' => (string) ($p->description ?? ''),
                    'price' => (float) $p->price,
                ])
                ->all();
        });

        return $catalog;
    }

    /**
     * Ask Gemini to pick product IDs from the provided catalog only.
     * Returns an array of integers guaranteed to be in the given catalog.
     *
     * @param  array<int, array{name?:string,description?:string|null,price?:float|int|string}>  $viewedProducts
     * @param  array<int, array{id:int,name:string,description:string,price:float}>  $catalog
     * @param  array<int,int>  $excludeIds
     * @return array<int,int>
     */
    public function recommendProductIdsFromCatalog(array $viewedProducts, array $catalog, int $limit = 3, array $excludeIds = []): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        // Build a stable cache key by hashing inputs
        $cacheKey = 'gemini_recommend_ids_'.md5(serialize([$viewedProducts, $catalog, $limit, array_values($excludeIds)]));
        if ($cached = Cache::get($cacheKey)) {
            return is_array($cached)
                ? array_values(array_filter($cached, static fn ($value) => is_int($value)))
                : [];
        }

        $prompt = $this->buildCatalogPrompt($viewedProducts, $catalog, $limit, $excludeIds);

        $ids = $this->fetchRecommendedIds($prompt);

        $valid = $this->filterCatalogIds($ids, $catalog, $excludeIds);

        Cache::put($cacheKey, $valid, now()->addSeconds(min(60, $this->catalogTtl)));

        return $valid;
    }

    /**
     * Build the catalog-aware recommendation prompt for Gemini.
     *
     * @param  array<int, array{name?:string,description?:string|null,price?:float|int|string}>  $viewedProducts
     * @param  array<int, array{id:int,name:string,description:string,price:float}>  $catalog
     * @param  array<int,int>  $excludeIds
     */
    protected function buildCatalogPrompt(array $viewedProducts, array $catalog, int $limit, array $excludeIds): string
    {
        $catalogLines = array_map(function (array $item): string {
            $desc = Str::limit($item['description'], 80);

            return sprintf('%d | %s | %0.2f | %s', (int) $item['id'], $item['name'], $item['price'], $desc);
        }, $catalog);

        $excludeList = implode(', ', array_map('intval', $excludeIds));

        $lines = [
            'You are a shopping assistant. Choose similar products ONLY from the catalog below.',
            'Return ONLY a JSON array of product IDs (integers) from the catalog and nothing else.',
            'Do not invent IDs. Do not repeat IDs. Do not include IDs in the excluded list.',
            '',
            'Last viewed products:',
        ];

        foreach ($viewedProducts as $p) {
            $name = $p['name'] ?? '';
            $desc = Str::limit((string) ($p['description'] ?? ''), 140);
            $price = $p['price'] ?? null;
            $lines[] = "- Name: {$name}; Price: {$price}; Desc: {$desc}";
        }

        $lines[] = '';
        $lines[] = 'Catalog (format: id | name | price | short description):';
        $lines = array_merge($lines, array_map(fn ($l) => '- '.$l, $catalogLines));
        $lines[] = '';
        if ($excludeList !== '') {
            $lines[] = 'Excluded IDs: ['.$excludeList.']';
        }
        $lines[] = 'Number of items to return: '.$limit;
        $lines[] = 'Respond like: [12, 5, 9]';

        return implode("\n", $lines);
    }

    /**
     * Call Gemini API and extract integer IDs from the response.
     *
     * @return array<int,int>
     */
    protected function fetchRecommendedIds(string $prompt): array
    {
        $text = $this->requestModelText($prompt, 0.2);

        return $text ? $this->extractJsonArrayOfIntegers($text) : [];
    }

    /**
     * Filter model-provided IDs to only those present in catalog and not excluded.
     *
     * @param  array<int,int>  $ids
     * @param  array<int, array{id:int,name:string,description:string,price:float}>  $catalog
     * @param  array<int,int>  $excludeIds
     * @return array<int,int>
     */
    protected function filterCatalogIds(array $ids, array $catalog, array $excludeIds): array
    {
        $catalogIds = array_map('intval', array_column($catalog, 'id'));
        $valid = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $catalogIds, true) && ! in_array($id, $excludeIds, true)) {
                $valid[] = $id;
            }
        }

        return array_values(array_unique($valid));
    }

    /**
     * Parse a JSON array of integers (or numeric strings) from text (fence tolerant).
     *
     * @return array<int,int>
     */
    protected function extractJsonArrayOfIntegers(string $text): array
    {
        $candidate = $text;
        if (preg_match('/```json\s*(\[.*?\])\s*```/is', $text, $m)) {
            $candidate = $m[1];
        } elseif (preg_match('/```\s*(\[.*?\])\s*```/is', $text, $m)) {
            $candidate = $m[1];
        }
        $decoded = json_decode($candidate, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($v) {
            if (is_int($v)) {
                return $v;
            }
            if (is_string($v) && is_numeric($v)) {
                return (int) $v;
            }

            return null;
        }, $decoded), static fn ($v) => ! is_null($v)));
    }

    /**
     * Make a model call and return trimmed text payload.
     */
    private function requestModelText(string $prompt, float $temperature): ?string
    {
        try {
            $url = sprintf('%s/models/%s:generateContent', $this->endpoint, $this->model);
            $http = Http::timeout($this->timeout)
                ->retry(1, 200)
                ->asJson()
                ->withQueryParameters(['key' => $this->apiKey]);

            $response = $http->post($url, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => $temperature,
                ],
            ]);

            if (! $response->ok()) {
                Log::warning('Gemini HTTP not OK', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $data = $response->json();
            $text = data_get($data, 'candidates.0.content.parts.0.text', '');
            if (! is_string($text)) {
                return null;
            }

            $text = trim($text);

            return $text === '' ? null : $text;
        } catch (Throwable $e) {
            Log::notice('Gemini request failed, falling back', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
