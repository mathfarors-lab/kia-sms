<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 100;
        $counter++;
        $year = now()->format('y');

        return [
            'student_code'  => "KIA-{$year}-" . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'name_en'       => $this->faker->name(),
            'name_km'       => null,
            'gender'        => $this->faker->randomElement(['male', 'female']),
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
            'address'       => 'Phnom Penh, Cambodia',
            'status'        => 'enrolled',
        ];
    }
}
