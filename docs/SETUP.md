# Setup checklist

1. Create a fresh Laravel project and install Socialite and Sanctum as shown in README.md.
2. Merge the Google entry from config/services.google.example.php into config/services.php.
3. Merge mobile_code_ttl from config/auth.mobile.example.php into the returned config/auth.php array.
4. Merge the two OAuth routes from routes/web.example.php into routes/web.php and the API routes from routes/api.example.php into routes/api.php.
5. Copy the migrations and MobileAuthCode model. Run php artisan migrate.
6. Add google_id to users with the included migration. Update the existing User model as described in docs/USER-MODEL.md, preserving its current traits and casts, and add Sanctum's HasApiTokens trait if not already present.
7. Set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI, MOBILE_APP_DEEP_LINK, and MOBILE_AUTH_CODE_TTL in .env. Register the exact HTTPS callback URL in Google Cloud Console.
8. Confirm your User model table includes name, email, password, and email_verified_at columns. Adjust the controller if your schema uses different names.
9. Run php artisan config:clear and php artisan route:list.

OAuth redirect and callback routes must use Laravel's web middleware/session. Do not move them into routes/api.php. The exchange endpoint is stateless and belongs in routes/api.php.

Security note: verify your Socialite/provider version returns email_verified in the user payload before enabling automatic linking to existing accounts. Test both first-time signup and existing-account login. Never expose GOOGLE_CLIENT_SECRET to the app.
