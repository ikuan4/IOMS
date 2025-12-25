@extends('layouts.dashboard')

@section('title','Create Branch')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Create Branch</h2>
            <p class="muted">Add a new branch to the system.</p>
        </div>
    </div>

    <div class="card" style="margin-top:12px; padding:16px;">
        <form action="{{ route('branches.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:12px;">
                <label style="display:block;margin-bottom:6px;font-size:15px;font-weight:600;">Branch Name</label>
                <input type="text" name="name" required style="width:100%;padding:14px 16px;border-radius:10px;border:1px solid #d0d7e0;font-size:15px;" />
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                <a href="{{ route('branches.index') }}" style="background:#f51607;color:#fff;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;text-decoration:none;display:flex;align-items:center;gap:8px;">
                    <span data-feather="x-circle"></span> Cancel
                </a>
                <button type="submit" style="background:#22c55e;color:white;padding:14px 24px;border-radius:10px;font-weight:1000;font-size:15px;border:none;display:flex;align-items:center;gap:8px;cursor:pointer;"><span data-feather="save"></span> Create Branch</button>
            </div>
        </form>
    </div>

@endsection
