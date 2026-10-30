<?php

use App\Models\Post;
use App\Models\User;

it('soft deletes the author\'s own post', function () {
    $post = Post::factory()->create();

    $this->actingAs($post->user)->delete(route('posts.destroy', $post))->assertRedirect();

    $this->assertSoftDeleted($post);
});

it('forbids deleting someone else\'s post', function () {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())->delete(route('posts.destroy', $post))->assertForbidden();

    $this->assertNotSoftDeleted($post);
});

it('requires login to delete', function () {
    $post = Post::factory()->create();

    $this->delete(route('posts.destroy', $post));

    $this->assertNotSoftDeleted($post);
});

it('hides deleted posts', function () {
    $post = Post::factory()->create(['title' => 'Gone soon']);
    $this->actingAs($post->user)->delete(route('posts.destroy', $post));
    auth()->logout();

    $this->get('/')->assertDontSee('Gone soon');
    $this->get('/posts/'.$post->slug)->assertNotFound();
});

it('restores the author\'s own deleted post', function () {
    $post = Post::factory()->create(['title' => 'Back again']);
    $this->actingAs($post->user)->delete(route('posts.destroy', $post));

    $this->actingAs($post->user)->post('/posts/'.$post->slug.'/restore')->assertRedirect();

    $this->assertNotSoftDeleted($post);
    $this->get('/')->assertSee('Back again');
});

it('forbids restoring someone else\'s post', function () {
    $post = Post::factory()->create();
    $this->actingAs($post->user)->delete(route('posts.destroy', $post));

    $this->actingAs(User::factory()->create())->post('/posts/'.$post->slug.'/restore')->assertForbidden();

    $this->assertSoftDeleted($post);
});
