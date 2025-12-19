<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $categories = ['Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Books', 'Toys'];
        $productNames = [
            'Wireless Headphones',
            'Smart Watch',
            'Laptop Stand',
            'Coffee Maker',
            'Running Shoes',
            'Yoga Mat',
            'Office Chair',
            'LED Desk Lamp',
            'Bluetooth Speaker',
            'Phone Case',
            'Water Bottle',
            'Backpack',
            'Sunglasses',
            'Fitness Tracker',
            'Portable Charger',
            'Keyboard',
            'Mouse Pad',
            'Webcam',
            'USB Cable',
            'Notebook Set',
            'Pen Collection',
            'Travel Mug',
            'Wall Clock',
            'Pillow',
            'Blanket',
        ];

        /** @var string $productName */
        $productName = $this->faker->randomElement($productNames);

        return [
            'name' => $productName.' - '.(string) $this->faker->word(),
            'description' => (string) $this->faker->sentence(15),
            'price' => (float) $this->faker->randomFloat(2, 9.99, 999.99),
            'image' => 'https://picsum.photos/seed/'.(string) $this->faker->unique()->numberBetween(1, 1000).'/400/300',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
