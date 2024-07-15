<?php

declare(strict_types=1);

namespace App\Repositories;

use App\User;
use Auth0\Laravel\{UserRepositoryAbstract, UserRepositoryContract};
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Exception;

final class UserRepository extends UserRepositoryAbstract implements UserRepositoryContract
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function fromAccessToken(array $user): ?Authenticatable
    {
        $accessToken = $this->request->header('Authorization');

        if (!$accessToken) {
            throw new Exception('Authorization header not available.');
        }

        // Remove the "Bearer " prefix if it exists
        if (strpos($accessToken, 'Bearer ') === 0) {
            $accessToken = substr($accessToken, 7);
        }

        try {
            // Use the access token to get user information from Auth0 /userinfo endpoint
            $client = new Client();
            $response = $client->request('GET', 'https://' . env('AUTH0_DOMAIN') . '/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            $userInfo = json_decode($response->getBody()->getContents(), true);

            if (!$userInfo) {
                throw new Exception('User information not available.');
            }

            $email = $userInfo['email'] ?? null;

            if (!$email) {
                throw new Exception('Email not available in the user profile.');
            }

            $currentuser = User::updateOrCreate(
                ['email' => $email],
                [
                    'auth0' => $userInfo['sub'],
                    'image' => $userInfo['picture'] ?? '',
                ]
            );

            return $currentuser;
        } catch (Exception $e) {
            // Log the error for debugging
            error_log('Error fetching user information: ' . $e->getMessage());
            throw $e;
        }
    }

    public function fromSession(array $user): ?Authenticatable
    {
        $identifier = $user['sub'] ?? $user['auth0'] ?? null;

        $profile = [
            'auth0' => $identifier,
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'email_verified' => in_array($user['email_verified'], [1, true], true),
        ];

        $cached = $this->withoutRecording(fn () => Cache::get('auth0_user_' . $identifier));

        if ($cached) {
            return $cached;
        }

        $user = null;

        if (null !== $identifier) {
            $user = User::where('auth0', $identifier)->first();
        }

        if (null === $user && isset($profile['email'])) {
            $user = User::where('email', $profile['email'])->first();
        }

        if (null !== $user) {
            $updates = [];

            if ($user->auth0 !== $profile['auth0']) {
                $updates['auth0'] = $profile['auth0'];
            }

            if ($user->name !== $profile['name']) {
                $updates['name'] = $profile['name'];
            }

            if ($user->email !== $profile['email']) {
                $updates['email'] = $profile['email'];
            }

            $emailVerified = in_array($user->email_verified, [1, true], true);

            if ($emailVerified !== $profile['email_verified']) {
                $updates['email_verified'] = $profile['email_verified'];
            }

            if ([] !== $updates) {
                $user->update($updates);
                $user->save();
            }

            if ([] === $updates && null !== $cached) {
                return $user;
            }
        }

        if (null === $user) {
            $profile['password'] = Hash::make(Str::random(32));
            $user = User::create($profile);
        }

        $this->withoutRecording(fn () => Cache::put('auth0_user_' . $identifier, $user, 30));

        return $user;
    }

    private function withoutRecording($callback): mixed
    {
        $telescope = '\Laravel\Telescope\Telescope';

        if (class_exists($telescope)) {
            return "$telescope"::withoutRecording($callback);
        }

        return call_user_func($callback);
    }
}
