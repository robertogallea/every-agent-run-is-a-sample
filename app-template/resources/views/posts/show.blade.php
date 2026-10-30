@extends('layouts.app')

@section('content')
<article>
    <h1>{{ $post->title }}</h1>
    <p>{{ $post->published_at->toFormattedDateString() }} · {{ $readingTime }}</p>
    <div>{!! nl2br(e($post->body)) !!}</div>
</article>
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan
@endsection
