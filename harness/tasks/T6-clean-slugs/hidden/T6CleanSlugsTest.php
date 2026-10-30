<?php

use App\Models\Post;
use App\Models\User;

it('uses the slugified title as the slug', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/posts', ['title' => 'My First Post', 'body' => 'Text', 'publish' => true]);

    expect($user->posts()->first()->slug)->toBe('my-first-post');
});

it('serves the post at the clean URL', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/posts', ['title' => 'Clean Address', 'body' => 'Text', 'publish' => true]);

    $this->get('/posts/clean-address')->assertOk()->assertSee('Clean Address');
});

it('still creates a post whose title is already taken', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/posts', ['title' => 'Same Title', 'body' => 'One', 'publish' => true]);

    $this->actingAs($user)->post('/posts', ['title' => 'Same Title', 'body' => 'Two', 'publish' => true])->assertRedirect();

    $slugs = $user->posts()->pluck('slug');
    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2)
        ->and($slugs->every(fn ($s) => str_starts_with($s, 'same-title')))->toBeTrue();
});

it('gives a duplicate title a readable slug', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/posts', ['title' => 'Repeat', 'body' => 'One', 'publish' => true]);
    $this->actingAs($user)->post('/posts', ['title' => 'Repeat', 'body' => 'Two', 'publish' => true]);

    $second = $user->posts()->where('body', 'Two')->first();
    expect($second->slug)->toMatch('/^repeat-\d+$/');
});

it('handles a title that is already taken by someone else', function () {
    Post::factory()->create(['title' => 'Shared', 'slug' => 'shared']);
    $user = User::factory()->create();

    $this->actingAs($user)->post('/posts', ['title' => 'Shared', 'body' => 'Mine', 'publish' => true])->assertRedirect();

    expect($user->posts()->first()->slug)->not->toBe('shared');
});

it('keeps existing URLs working', function () {
    $post = Post::factory()->create(['slug' => 'old-style-post-ab12cd']);

    $this->get('/posts/old-style-post-ab12cd')->assertOk();
});
