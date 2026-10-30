<?php

use App\Models\Post;

it('does not list scheduled posts', function () {
    Post::factory()->create(['title' => 'Launch day', 'published_at' => now()->addDay()]);

    $this->get('/')->assertDontSee('Launch day');
});

it('does not show a scheduled post page', function () {
    $post = Post::factory()->create(['published_at' => now()->addDay()]);

    $this->get('/posts/'.$post->slug)->assertNotFound();
});

it('shows the post once its date has passed', function () {
    $post = Post::factory()->create(['title' => 'Now live', 'published_at' => now()->addDay()]);

    $this->travel(2)->days();

    $this->get('/')->assertSee('Now live');
    $this->get('/posts/'.$post->slug)->assertOk();
});

it('keeps published posts visible and drafts hidden', function () {
    Post::factory()->create(['title' => 'Old news', 'published_at' => now()->subWeek()]);
    Post::factory()->draft()->create(['title' => 'Unfinished']);

    $this->get('/')->assertSee('Old news')->assertDontSee('Unfinished');
});

it('excludes scheduled posts from the published scope', function () {
    Post::factory()->create(['published_at' => now()->addHour()]);
    Post::factory()->create(['published_at' => now()->subHour()]);

    expect(Post::published()->count())->toBe(1);
});
