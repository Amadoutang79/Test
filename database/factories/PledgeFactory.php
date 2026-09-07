<?php

namespace Database\Factories;

use App\Models\Cause;
use App\Models\Pledge;
use Illuminate\Database\Eloquent\Factories\Factory;

class PledgeFactory extends Factory
{
    protected $model = Pledge::class;

    public function definition()
    {
        return [
            'cause_id' => Cause::factory(),
            'pledger_name' => $this->faker->name(),
            'pledger_email' => $this->faker->safeEmail(),
            'amount' => $this->faker->numberBetween(10, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
