<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['google', 'apple']),
            'provider_user_id' => $this->faker->uuid(),
            'provider_email' => $this->faker->safeEmail(),
            'provider_name' => $this->faker->name(),
            'provider_avatar' => $this->faker->imageUrl(),
            'access_token' => $this->faker->sha1(),
            'refresh_token' => $this->faker->sha1(),
        ];
    }
}
