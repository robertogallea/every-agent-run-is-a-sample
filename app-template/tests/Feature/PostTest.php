<?php

use App\Models\Post;
use App\Models\User;

it('lists published posts and hides drafts', function () {
    $published = Post::factory()->create(['title' => 'Hello world']);
    $draft = Post::factory()->draft()->create(['title' => 'Secret draft']);

    $this->get('/')
        ->assertOk()
        ->assertSee('Hello world')
        ->assertDontSee('Secret draft');
});

it('shows a published post', function () {
    $post = Post::factory()->create(['title' => 'Readable']);

    $this->get(route('posts.show', $post))->assertOk()->assertSee('Readable');
});

it('returns 404 for a draft', function () {
    $post = Post::factory()->draft()->create();

    $this->get(route('posts.show', $post))->assertNotFound();
});

it('lets an authenticated user create a post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/posts', ['title' => 'Mine', 'body' => 'Text', 'publish' => true])
        ->assertRedirect();

    expect($user->posts()->where('title', 'Mine')->exists())->toBeTrue();
});

it('requires login to create a post', function () {
    $this->get('/posts/create')->assertRedirect(route('login'));
});

it('lets the author edit their post', function () {
    $post = Post::factory()->create();

    $this->actingAs($post->user)
        ->put(route('posts.update', $post), ['title' => 'Updated', 'body' => 'New body'])
        ->assertRedirect();

    expect($post->fresh()->title)->toBe('Updated');
});

it('forbids editing someone else\'s post', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('posts.update', $post), ['title' => 'Hijacked', 'body' => 'x'])
        ->assertForbidden();
});
