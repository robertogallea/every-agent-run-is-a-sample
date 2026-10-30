<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('makes users verifiable', function () {
    expect(new User)->toBeInstanceOf(MustVerifyEmail::class);
});

it('sends a verification email on registration', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Ada', 'email' => 'ada@example.com',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ]);

    Notification::assertSentTo(User::where('email', 'ada@example.com')->first(), VerifyEmail::class);
});

it('keeps unverified users away from the new post page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/posts/create')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->post('/posts', ['title' => 'x', 'body' => 'y', 'publish' => true]);

    expect($user->posts()->count())->toBe(0);
});

it('shows the verification notice and resends the email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
    $this->actingAs($user)->post(route('verification.send'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('verifies the user from the signed link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a tampered verification link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1('someone-else@example.com'),
    ]);

    $this->actingAs($user)->get($url);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('lets verified users create posts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/posts/create')->assertOk();
});
