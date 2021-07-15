<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => 1,
            'order_id' => $this->faker->numerify('order-#######'),
            'product' => $this->faker->words(2, true),
            'amount' => $this->faker->numberBetween(1000, 100000),
            'tax' => $this->faker->numberBetween(100, 10000),
            'total' => $this->faker->numberBetween(10000, 1000000),
        ];
    }
}
