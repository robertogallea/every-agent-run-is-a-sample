<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::published()->latest('published_at')->get();

        $readingTimes = [];
        foreach ($posts as $post) {
            $body = preg_replace('/```.*?```/s', '', $post->body) ?? '';
            $minutes = max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));
            $readingTimes[$post->id] = $minutes.' min read';
        }

        return view('posts.index', ['posts' => $posts, 'readingTimes' => $readingTimes]);
    }

    public function show(Post $post): View
    {
        abort_unless($post->published_at !== null, 404);

        $body = preg_replace('/```.*?```/s', '', $post->body) ?? '';
        $minutes = max(1, (int) ceil(str_word_count(strip_tags($body)) / 200));

        return view('posts.show', ['post' => $post, 'readingTime' => $minutes.' min read']);
    }

    public function create(): View
    {
        return view('posts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'publish' => ['boolean'],
        ]);

        $post = $request->user()->posts()->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'body' => $data['body'],
            'published_at' => ($data['publish'] ?? false) ? now() : null,
        ]);

        return redirect()->route('posts.edit', $post);
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        return view('posts.edit', ['post' => $post]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $post->update($data);

        return redirect()->route('posts.edit', $post);
    }
}
