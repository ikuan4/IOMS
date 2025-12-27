@extends('layouts.dashboard')

@section('title', 'Edit User')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Edit User</h2>
            <p class="muted">Update user details, active status, and bounce data.</p>
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

    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="user-form" enctype="multipart/form-data">
        @method('PUT')
        @include('users._form')
    </form>
@endsection
