@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div style="display:flex; align-items:center; gap:.75rem; margin-bottom:1.5rem;">
    <h1 style="margin:0;">Admin Dashboard</h1>
    <span class="badge admin">app-admin</span>
</div>

<div class="card">
    <p style="color:#475569; margin:0;">This page is only accessible to users with the <code>app-admin</code> Keycloak realm role.</p>
</div>

<div class="card">
    <h3 style="margin-top:0;">Logged-in admin</h3>
    <p><strong>Name:</strong> {{ $user->name }}</p>
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Keycloak ID:</strong> <code>{{ $user->keycloak_id }}</code></p>
</div>
@endsection
