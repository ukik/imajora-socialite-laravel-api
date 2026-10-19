# User model requirements

Do not overwrite an existing User model. Merge the following into the existing model:

```php
protected $fillable = [
    // keep your existing fillable columns
    'name',
    'email',
    'password',
    'google_id',
    'email_verified_at',
];
```

Add a nullable unique `google_id` column to the users table:

```bash
php artisan make:migration add_google_id_to_users_table --table=users
```

Migration body:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('google_id')->nullable()->unique()->after('email');
});
```

Add `google_id` to `down()` with `dropUnique(['google_id'])` and `dropColumn('google_id')` as appropriate for your database.

Ensure the User model uses `Laravel\Sanctum\HasApiTokens`. Preserve all existing traits and casts.
