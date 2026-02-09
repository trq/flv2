<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_redirected_to_google_provider(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.social.redirect', ['provider' => 'google']))
            ->assertRedirect();
    }

    public function test_callback_creates_user_and_social_account_for_new_google_user(): void
    {
        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-123',
            'name' => 'Flowly User',
            'email' => 'flowly@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ])->setToken('fake-token'));

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'flowly@example.com',
        ]);

        $user = User::query()->where('email', 'flowly@example.com')->firstOrFail();

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    public function test_callback_links_to_existing_user_with_matching_email_without_creating_duplicate_account(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        Socialite::fake('apple', (new SocialiteUser)->map([
            'id' => 'apple-321',
            'name' => 'Existing User',
            'email' => 'existing@example.com',
        ])->setToken('fake-token'));

        $response = $this->get(route('auth.social.callback', ['provider' => 'apple']));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->assertSame(1, User::query()->count());

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->getKey(),
            'provider' => 'apple',
            'provider_user_id' => 'apple-321',
        ]);
    }

    public function test_callback_uses_existing_linked_social_account_for_login(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
        ]);

        SocialAccount::query()->create([
            'user_id' => $user->getKey(),
            'provider' => 'google',
            'provider_user_id' => 'google-linked',
            'provider_email' => 'other@example.com',
            'provider_name' => 'Linked',
            'provider_avatar' => null,
            'access_token' => 'old-token',
            'refresh_token' => null,
        ]);

        Socialite::fake('google', (new SocialiteUser)->map([
            'id' => 'google-linked',
            'name' => 'Different Name',
            'email' => 'another-email@example.com',
        ])->setToken('new-token'));

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, SocialAccount::query()->count());
    }

    public function test_unsupported_provider_is_not_allowed(): void
    {
        $this->get(route('auth.social.redirect', ['provider' => 'facebook']))
            ->assertNotFound();
    }
}
