<?php

use App\Models\User;


function failLogin($test, string $email, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        $test->post('/login', ['email' => $email, 'password' => 'wrong-password']);
    }
}

it('blocks the correct password after five failures', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    failLogin($this, $user->email, 5);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

    $this->assertGuest();
});

it('explains the lockout on the email field', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    failLogin($this, $user->email, 5);

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

    expect(in_array($response->status(), [302, 429]))->toBeTrue();
    if ($response->status() === 302) {
        $response->assertSessionHasErrors('email');
    }
});

it('still allows login after four failures', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    failLogin($this, $user->email, 4);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

    $this->assertAuthenticatedAs($user);
});

it('does not block other emails', function () {
    $victim = User::factory()->create(['password' => 'secret-password']);
    $other = User::factory()->create(['password' => 'other-password']);
    failLogin($this, $victim->email, 6);

    $this->post('/login', ['email' => $other->email, 'password' => 'other-password']);

    $this->assertAuthenticatedAs($other);
});

it('resets the counter after a successful login', function () {
    $user = User::factory()->create(['password' => 'secret-password']);
    failLogin($this, $user->email, 4);
    $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);
    $this->post('/logout');
    failLogin($this, $user->email, 4);

    $this->post('/login', ['email' => $user->email, 'password' => 'secret-password']);

    $this->assertAuthenticatedAs($user);
});
