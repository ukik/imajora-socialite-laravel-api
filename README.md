# IMAJORA Socialite Laravel API

Starter implementation for Google OAuth login from a Quasar + Cordova Android app.

## Stack
- Laravel 12 (compatible with Laravel 11 with small middleware/config differences)
- Laravel Socialite
- Laravel Sanctum personal access tokens
- One-time mobile exchange codes (short-lived, single use)

## Install in a fresh Laravel project
```bash
composer create-project laravel/laravel imajora-socialite-laravel-api
cd imajora-socialite-laravel-api
composer require laravel/socialite laravel/sanctum
php artisan install:api
```

Copy the files in this repository into the matching paths of that Laravel project. This repository is an integration starter, not a complete Laravel framework distribution; the framework's generated files are intentionally not vendored.

Configure `.env` using `.env.example`, set Google OAuth's authorized redirect URI to:
`https://YOUR_API_HOST/auth/google/callback`

Then run:
```bash
php artisan migrate
php artisan config:clear
php artisan route:list
php artisan serve
```

## Google Cloud
Create an OAuth 2.0 Web application client. Add your backend callback URL as an authorized redirect URI. Keep `GOOGLE_CLIENT_SECRET` on the backend only.

## Flow
1. App opens `GET /auth/google/mobile?state=RANDOM_STATE` in the system browser.
2. Laravel redirects to Google using Socialite.
3. Callback validates the app state stored in the Laravel session, finds/creates the user, and redirects to `imajora://auth/callback?code=...`.
4. App exchanges that one-time code with `POST /api/mobile-auth/exchange`.
5. API returns a Sanctum token; authenticated API requests use `Authorization: Bearer TOKEN`.

## Important
- Register the Android custom URL scheme `imajora` in Cordova.
- Test deep links on a real Android device.
- For production, use HTTPS, restrict OAuth redirect URIs, configure rate limiting, and use secure storage for tokens.
- Do not commit real secrets or production tokens.
- If your existing User model has custom fields/traits, merge the required changes rather than replacing the model.
