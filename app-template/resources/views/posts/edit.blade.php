@extends('layouts.app')

@section('content')
<h1>Edit post</h1>
<form method="POST" action="{{ route('posts.update', $post) }}">
    @csrf
    @method('PUT')
    <input name="title" value="{{ old('title', $post->title) }}">
    <textarea name="body">{{ old('body', $post->body) }}</textarea>
    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    <button>Save</button>
</form>
@endsection
