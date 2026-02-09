<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_PROVIDERS = ['google', 'apple'];

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $resolvedProvider = $this->resolveProvider($provider);

        return Socialite::driver($resolvedProvider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $resolvedProvider = $this->resolveProvider($provider);
        $socialiteUser = Socialite::driver($resolvedProvider)->user();
        $providerUserId = (string) $socialiteUser->getId();

        if ($providerUserId === '') {
            return redirect()->route('login')->withErrors([
                'social' => 'Unable to retrieve an account identifier from the provider.',
            ]);
        }

        $existingSocialAccount = SocialAccount::query()
            ->where('provider', $resolvedProvider)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($existingSocialAccount !== null) {
            $this->syncSocialAccount($existingSocialAccount, $socialiteUser);
            $existingSocialAccount->save();
            Auth::login($existingSocialAccount->user, true);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $email = $socialiteUser->getEmail();

        if (! is_string($email) || $email === '') {
            return redirect()->route('login')->withErrors([
                'social' => 'Your provider account must include an email to sign in.',
            ]);
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $socialiteUser->getName() ?: $email,
                'password' => Hash::make(Str::random(40)),
            ],
        );

        $tokenData = $this->tokenData($socialiteUser);

        SocialAccount::query()->updateOrCreate(
            [
                'provider' => $resolvedProvider,
                'provider_user_id' => $providerUserId,
            ],
            [
                'user_id' => $user->getKey(),
                'provider_email' => $email,
                'provider_name' => $socialiteUser->getName(),
                'provider_avatar' => $socialiteUser->getAvatar(),
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
            ],
        );

        Auth::login($user, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function resolveProvider(string $provider): string
    {
        $resolvedProvider = Str::lower(trim($provider));

        abort_unless(in_array($resolvedProvider, self::SUPPORTED_PROVIDERS, true), 404);

        return $resolvedProvider;
    }

    private function syncSocialAccount(SocialAccount $socialAccount, SocialiteUser $socialiteUser): void
    {
        $tokenData = $this->tokenData($socialiteUser);

        $socialAccount->forceFill([
            'provider_email' => $socialiteUser->getEmail(),
            'provider_name' => $socialiteUser->getName(),
            'provider_avatar' => $socialiteUser->getAvatar(),
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
        ]);
    }

    /**
     * @return array{access_token: string|null, refresh_token: string|null}
     */
    private function tokenData(object $socialiteUser): array
    {
        $properties = get_object_vars($socialiteUser);
        $accessToken = $properties['token'] ?? null;
        $refreshToken = $properties['refreshToken'] ?? null;

        return [
            'access_token' => is_string($accessToken) ? $accessToken : null,
            'refresh_token' => is_string($refreshToken) ? $refreshToken : null,
        ];
    }
}
