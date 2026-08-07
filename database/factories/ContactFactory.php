<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::inRandomOrder()->value('id'),
            'first_name' => fake('ja_JP')->lastName(),
            'last_name' => fake('ja_JP')->firstName(),
            'gender' => fake()->numberBetween(1, 3),
            'email' => fake()->safeEmail(),
            'tel' => fake()->numerify('080########'),
            'address' => fake('ja_JP')->address(),
            'building' => fake()->optional()->numerify('テストマンション###'),
            'detail' => fake()->randomElement([
                '商品の配送状況について確認したいです。',
                '商品を交換したいです。',
                '届いた商品に不具合がありました。',
                'サービスについて質問があります。',
                'その他のお問い合わせです。',
            ]),
        ];
    }
}
