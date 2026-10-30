<?php

use App\Models\User;

function registerAs($test, string $email): void
{
    $test->post('/register', [
        'name' => 'Ada', 'email' => $email,
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ]);
    $test->post('/logout');
}

it('logs in with a lowercase version of a mixed-case registration', function () {
    registerAs($this, 'Ada@Example.com');

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'secret-password']);

    $this->assertAuthenticated();
});

it('logs in with the same mixed case used at registration', function () {
    registerAs($this, 'Ada@Example.com');

    $this->post('/login', ['email' => 'Ada@Example.com', 'password' => 'secret-password']);

    $this->assertAuthenticated();
});

it('logs in with uppercase input for a lowercase account', function () {
    $user = User::factory()->create(['email' => 'linus@example.com', 'password' => 'secret-password']);

    $this->post('/login', ['email' => 'LINUS@EXAMPLE.COM', 'password' => 'secret-password']);

    $this->assertAuthenticatedAs($user);
});

it('rejects a registration that differs only by case', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post('/register', [
        'name' => 'Ada Again', 'email' => 'ADA@example.com',
        'password' => 'secret-password', 'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');

    expect(User::count())->toBe(1);
});

it('still rejects a wrong password', function () {
    User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

    $this->post('/login', ['email' => 'ADA@example.com', 'password' => 'wrong-password']);

    $this->assertGuest();
});
