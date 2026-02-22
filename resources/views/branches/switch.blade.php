@extends('layouts.dashboard')

@section('title', 'Switch Branch')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>Switch Branch</h2>
            <p class="muted">Choose a branch to set as your active context.</p>
        </div>
    </div>

    <div class="card" style="margin-top:12px;max-width:520px;">
        <form method="POST" action="{{ route('branches.switch.post') }}" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            <label for="branch_id" style="font-weight:600;">Branch</label>
            <select id="branch_id" name="branch_id" required style="padding:12px;border-radius:8px;border:1px solid #d0d7e0;">
                <option value="">-- Select Branch --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            @error('branch_id')
                <div style="color:#dc2626;font-size:13px;">{{ $message }}</div>
            @enderror
            <button type="submit" style="background:#22c55e;color:white;padding:12px 16px;border-radius:8px;border:none;font-weight:700;">Switch</button>
        </form>
    </div>
@endsection
