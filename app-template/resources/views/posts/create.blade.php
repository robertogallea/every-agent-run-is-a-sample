@extends('layouts.app')

@section('content')
<h1>New post</h1>
<form method="POST" action="{{ route('posts.store') }}">
    @csrf
    <input name="title" value="{{ old('title') }}" placeholder="Title">
    <textarea name="body">{{ old('body') }}</textarea>
    <label><input type="checkbox" name="publish" value="1"> Publish now</label>
    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    <button>Save</button>
</form>
@endsection
