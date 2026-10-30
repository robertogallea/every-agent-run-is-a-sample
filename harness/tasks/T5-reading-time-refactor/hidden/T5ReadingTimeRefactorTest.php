<?php

use App\Models\Post;
use App\Support\ReadingTime;

it('exposes the reading time service', function () {
    expect(ReadingTime::for('Short post.'))->toBe('1 min read');
});

it('counts numbers as words', function () {
    expect(ReadingTime::for(str_repeat('2026 ', 300).str_repeat('word ', 100)))->toBe('2 min read');
});

it('still rounds up at 200 words per minute', function () {
    expect(ReadingTime::for(str_repeat('word ', 401)))->toBe('3 min read');
});

it('still ignores fenced code blocks', function () {
    $code = "```php\n".str_repeat('$variable = call(); ', 300)."\n```";

    expect(ReadingTime::for("Intro.\n\n".$code."\n\nOutro."))->toBe('1 min read');
});

it('uses the service on both pages', function () {
    $post = Post::factory()->create(['body' => str_repeat('42 ', 250)]);

    $this->get('/')->assertSee('2 min read');
    $this->get(route('posts.show', $post))->assertSee('2 min read');
});

it('removed the duplicated calculation from the controller', function () {
    $source = file_get_contents(app_path('Http/Controllers/PostController.php'));

    expect(substr_count($source, 'str_word_count'))->toBe(0);
});
