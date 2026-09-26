<?php

namespace Database\Factories;

use App\Models\Barang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barang>
 */
class BarangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('BRG-####'),
            'harga' => fake()->randomFloat(2, 1000, 10000000),
            'stok' => fake()->numberBetween(0, 100),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
