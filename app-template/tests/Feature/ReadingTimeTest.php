<?php

use App\Models\Post;

it('shows at least one minute of reading time', function () {
    $post = Post::factory()->create(['body' => 'Short post.']);

    $this->get(route('posts.show', $post))->assertSee('1 min read');
});

it('rounds reading time up at 200 words per minute', function () {
    $post = Post::factory()->create(['body' => str_repeat('word ', 401)]);

    $this->get(route('posts.show', $post))->assertSee('3 min read');
});

it('does not count words inside fenced code blocks', function () {
    $code = "```php\n".str_repeat('$variable = call(); ', 300)."\n```";
    $post = Post::factory()->create(['body' => "Intro paragraph.\n\n".$code."\n\nOutro."]);

    $this->get(route('posts.show', $post))->assertSee('1 min read');
});

it('shows reading time on the index', function () {
    Post::factory()->create(['body' => str_repeat('word ', 250)]);

    $this->get('/')->assertSee('2 min read');
});
