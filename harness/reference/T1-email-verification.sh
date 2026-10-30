#!/usr/bin/env bash
set -e
mkdir -p resources/views/auth
cat > resources/views/auth/verify-email.blade.php <<'BLADE'
@extends('layouts.app')

@section('content')
<h1>Verify your email</h1>
<p>We sent a verification link to your email address.</p>
<form method="POST" action="{{ route('verification.send') }}">@csrf<button>Resend</button></form>
@endsection
BLADE
python3 - <<'PY'
p='app/Models/User.php'; s=open(p).read()
s=s.replace("// use Illuminate\\Contracts\\Auth\\MustVerifyEmail;","use Illuminate\\Contracts\\Auth\\MustVerifyEmail;")
s=s.replace("class User extends Authenticatable\n","class User extends Authenticatable implements MustVerifyEmail\n")
open(p,'w').write(s)
import subprocess
p='app/Http/Controllers/Auth/RegisterController.php'; s=open(p).read()
s=s.replace("use Illuminate\\Http\\RedirectResponse;","use Illuminate\\Auth\\Events\\Registered;\nuse Illuminate\\Http\\RedirectResponse;")
s=s.replace("        Auth::login($user);","        event(new Registered($user));\n\n        Auth::login($user);")
open(p,'w').write(s)
p='routes/web.php'; s=open(p).read()
s=s.replace("use Illuminate\\Support\\Facades\\Route;","use Illuminate\\Foundation\\Auth\\EmailVerificationRequest;\nuse Illuminate\\Http\\Request;\nuse Illuminate\\Support\\Facades\\Route;")
s=s.replace("""    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');""","""    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('posts.index');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');
    Route::middleware('verified')->group(function () {
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    });""")
open(p,'w').write(s)
PY
./vendor/bin/pint -q app/Models/User.php
