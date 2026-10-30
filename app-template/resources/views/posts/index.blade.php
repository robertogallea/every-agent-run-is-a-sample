@extends('layouts.app')

@section('content')
<h1>Posts</h1>
@forelse ($posts as $post)
    <article>
        <h2><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h2>
        <p>{{ $post->published_at->toFormattedDateString() }} · {{ $readingTimes[$post->id] }}</p>
    </article>
@empty
    <p>No posts yet.</p>
@endforelse
@endsection
