@extends('layouts.app')

@section('content')
<h1>Register</h1>
<form method="POST" action="{{ route('register') }}">
    @csrf
    <input name="name" value="{{ old('name') }}" placeholder="Name">
    <input name="email" type="email" value="{{ old('email') }}" placeholder="Email">
    <input name="password" type="password" placeholder="Password">
    <input name="password_confirmation" type="password" placeholder="Confirm password">
    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    <button>Register</button>
</form>
@endsection
