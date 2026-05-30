@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 style="margin-bottom:1.5rem;">Dashboard</h1>

<div class="card">
    <h2 style="margin-top:0;">Welcome, {{ $user->name }}</h2>
    <p style="color:#475569; margin:.25rem 0;">{{ $user->email }}</p>

    <div style="margin-top:1rem;">
        <strong>Roles:</strong>
        @forelse($user->keycloak_roles ?? [] as $role)
            <span class="badge {{ $role === 'app-admin' ? 'admin' : ($role === 'app-moderator' ? 'moderator' : '') }}">
                {{ $role }}
            </span>
        @empty
            <span style="color:#94a3b8; font-size:.875rem;">No roles assigned</span>
        @endforelse
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Permissions</h3>
    <table style="width:100%; border-collapse:collapse; font-size:.875rem;">
        <thead>
            <tr style="border-bottom:2px solid #e2e8f0;">
                <th style="text-align:left; padding:.5rem;">Ability</th>
                <th style="text-align:left; padding:.5rem;">Access</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['admin', 'manage-users', 'manage-content', 'view-reports', 'view-content'] as $ability)
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:.5rem;"><code>{{ $ability }}</code></td>
                <td style="padding:.5rem;">
                    @can($ability)
                        <span style="color:#16a34a; font-weight:600;">✓ Allowed</span>
                    @else
                        <span style="color:#dc2626;">✗ Denied</span>
                    @endcan
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($user->isAdmin())
<div class="card" style="border-color:#fca5a5; background:#fff5f5;">
    <h3 style="margin-top:0; color:#9d174d;">Admin Panel</h3>
    <p style="color:#be185d; font-size:.875rem;">You have admin access.</p>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-danger">Go to Admin</a>
</div>
@endif
@endsection
