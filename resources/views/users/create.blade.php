@extends('layouts.dashboard')

@section('title', 'Add User')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Add User</h2>
            <p class="muted">Create a new user account.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-top:12px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li style="color:#dc2626;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}" class="user-form">
        @include('users._form')
    </form>
@endsection
