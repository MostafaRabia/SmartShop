<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_recommend_product_ids_filters_invalid_and_uses_cache(): void
    {
        config([
            'gemini.api_key' => 'test-key',
            'gemini.model' => 'test-model',
            'gemini.endpoint' => 'https://gemini.test',
            'gemini.timeout' => 1,
            'gemini.catalog_cache_seconds' => 30,
        ]);

        $catalog = [
            ['id' => 1, 'name' => 'A', 'description' => 'Desc', 'price' => 10.0],
            ['id' => 2, 'name' => 'B', 'description' => 'Desc', 'price' => 12.0],
            ['id' => 3, 'name' => 'C', 'description' => 'Desc', 'price' => 14.0],
        ];

        Http::fake([
            'https://gemini.test/models/test-model:generateContent*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '[1, "2", 999, "foo"]'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new GeminiService;

        $ids = $service->recommendProductIdsFromCatalog([
            ['name' => 'One', 'price' => 10],
        ], $catalog, 3, [2]);

        $this->assertSame([1], $ids);

        $service->recommendProductIdsFromCatalog([
            ['name' => 'One', 'price' => 10],
        ], $catalog, 3, [2]);

        Http::assertSentCount(1);
    }

    public function test_get_catalog_projection_returns_products(): void
    {
        Product::factory()->count(2)->create();

        $service = new GeminiService;

        $catalog = $service->getCatalogProjection();

        $this->assertCount(2, $catalog);
        $this->assertArrayHasKey('id', $catalog[0]);
        $this->assertArrayHasKey('name', $catalog[0]);
        $this->assertArrayHasKey('description', $catalog[0]);
        $this->assertArrayHasKey('price', $catalog[0]);
    }
}
