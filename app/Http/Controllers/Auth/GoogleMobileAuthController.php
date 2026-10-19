<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MobileAuthCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleMobileAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $request->validate(['state' => ['required', 'string', 'min:32', 'max:200']]);
        $request->session()->put('mobile_oauth_state', $request->string('state')->toString());

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request)
    {
        $expectedState = $request->session()->pull('mobile_oauth_state');
        $returnedState = $request->query('state');

        // Socialite validates its own OAuth state as part of user(); this separate
        // state binds the resulting browser session to the app instance.
        try {
            $googleUser = Socialite::driver('google')->user();

            if (!$expectedState || !$returnedState) {
                return $this->redirectError('invalid_state');
            }

            $user = DB::transaction(function () use ($googleUser) {
                $email = strtolower((string) $googleUser->getEmail());

                if (!$email || !$googleUser->getId()) {
                    throw new \RuntimeException('Google did not return a usable account.');
                }

                $user = User::where('google_id', $googleUser->getId())->first();

                if (!$user) {
                    $user = User::where('email', $email)->first();

                    if ($user) {
                        // Link an existing email only when Google marks it verified.
                        $verified = (bool) data_get($googleUser->user, 'email_verified', false);
                        if (!$verified) {
                            throw new \RuntimeException('Google email is not verified.');
                        }
                        $user->google_id = $googleUser->getId();
                        $user->save();
                    } else {
                        $user = User::create([
                            'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                            'email' => $email,
                            'google_id' => $googleUser->getId(),
                            'password' => Hash::make(Str::random(64)),
                            'email_verified_at' => now(),
                        ]);
                    }
                }

                return $user;
            });

            $plainCode = Str::random(64);
            MobileAuthCode::create([
                'user_id' => $user->id,
                'code_hash' => hash('sha256', $plainCode),
                'expires_at' => now()->addSeconds((int) config('auth.mobile_code_ttl', 120)),
            ]);

            $deepLink = rtrim((string) env('MOBILE_APP_DEEP_LINK', 'imajora://auth/callback'), '/');
            return redirect()->away($deepLink . '?code=' . urlencode($plainCode) . '&state=' . urlencode($expectedState));
        } catch (Throwable $e) {
            report($e);
            return $this->redirectError('google_login_failed');
        }
    }

    public function exchange(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:32', 'max:200'],
        ]);

        $codeHash = hash('sha256', $data['code']);
        $record = MobileAuthCode::where('code_hash', $codeHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Kode login tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $token = DB::transaction(function () use ($record) {
            $locked = MobileAuthCode::whereKey($record->id)->lockForUpdate()->first();

            if (!$locked || $locked->used_at || $locked->expires_at->isPast()) {
                return null;
            }

            $locked->used_at = now();
            $locked->save();

            return $locked->user->createToken('cordova-google-login')->plainTextToken;
        });

        if (!$token) {
            return response()->json(['message' => 'Kode login sudah digunakan atau kedaluwarsa.'], 422);
        }

        return response()->json(['token' => $token, 'token_type' => 'Bearer']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    private function redirectError(string $error)
    {
        $deepLink = rtrim((string) env('MOBILE_APP_DEEP_LINK', 'imajora://auth/callback'), '/');
        return redirect()->away($deepLink . '?error=' . urlencode($error));
    }
}
