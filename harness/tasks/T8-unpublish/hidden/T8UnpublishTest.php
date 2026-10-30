<?php

use App\Models\Post;
use App\Models\User;

it('unpublishes when the box is unchecked', function () {
    $post = Post::factory()->create(['title' => 'Going away']);

    $this->actingAs($post->user)->put(route('posts.update', $post), ['title' => 'Going away', 'body' => 'b', 'publish' => '0']);

    expect($post->fresh()->published_at)->toBeNull();
    auth()->logout();
    $this->get('/')->assertDontSee('Going away');
});

it('publishes a draft when the box is checked', function () {
    $post = Post::factory()->draft()->create(['title' => 'Coming soon']);

    $this->actingAs($post->user)->put(route('posts.update', $post), ['title' => 'Coming soon', 'body' => 'b', 'publish' => '1']);

    expect($post->fresh()->published_at)->not->toBeNull();
    auth()->logout();
    $this->get('/')->assertSee('Coming soon');
});

it('keeps the original date when an already published post stays published', function () {
    $date = now()->subMonth()->startOfSecond();
    $post = Post::factory()->create(['published_at' => $date]);

    $this->actingAs($post->user)->put(route('posts.update', $post), ['title' => 'Edited', 'body' => 'b', 'publish' => '1']);

    expect($post->fresh()->published_at->equalTo($date))->toBeTrue();
});

it('shows the current state on the edit form', function () {
    $published = Post::factory()->create();
    $draft = Post::factory()->draft()->create(['user_id' => $published->user_id]);

    $this->actingAs($published->user)->get(route('posts.edit', $published))->assertSee('checked', false);
    $this->actingAs($published->user)->get(route('posts.edit', $draft))->assertDontSee('checked', false);
});

it('forbids others from unpublishing', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('posts.update', $post), ['title' => 'x', 'body' => 'y'])
        ->assertForbidden();

    expect($post->fresh()->published_at)->not->toBeNull();
});
