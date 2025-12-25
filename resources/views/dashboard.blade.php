@extends('layouts.dashboard')

@section('title','Dashboard')

@section('content')
    <div class="header-card">
        <div>
            <h3>Dashboard</h3>
            <p class="muted">Welcome, {{ auth()->user()?->name ?? 'User' }}.</p>
        </div>
        <!-- logout button removed; use header menu to log out -->
    </div>
@endsection
