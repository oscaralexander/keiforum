<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }

            Auth::login($user, remember: true);

            return redirect()->intended(route('home'));
        }

        session([
            'oauth.google' => [
                'avatar' => $googleUser->getAvatar(),
                'email' => $googleUser->getEmail(),
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
            ],
        ]);

        return redirect()->route('register-oauth');
    }
}
