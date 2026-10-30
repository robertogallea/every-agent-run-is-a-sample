#!/usr/bin/env bash
set -e
python3 - <<'PY'
p='app/Http/Controllers/Auth/LoginController.php'; s=open(p).read()
s=s.replace("use Illuminate\\Support\\Facades\\Auth;","use Illuminate\\Support\\Facades\\Auth;\nuse Illuminate\\Support\\Facades\\RateLimiter;\nuse Illuminate\\Support\\Str;")
s=s.replace("""        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
""","""        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
""")
open(p,'w').write(s)
PY
