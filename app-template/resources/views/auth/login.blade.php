@extends('layouts.app')

@section('content')
<h1>Log in</h1>
<form method="POST" action="{{ route('login') }}">
    @csrf
    <input name="email" type="email" value="{{ old('email') }}" placeholder="Email">
    <input name="password" type="password" placeholder="Password">
    <label><input type="checkbox" name="remember" value="1"> Remember me</label>
    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    <button>Log in</button>
</form>
@endsection
