@php
    $permissions = App\Models\AuthorityModel::join('table_urls', 'table_urls.id', '=', 'authority.linkName_id')
        ->where('authority.user_id', Auth::id())
        ->get();
@endphp

<aside class="sidebar">
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand-mark">✦</span><span>3D Viewer</span>
    </a>

    <nav>
        <p class="nav-caption">Main menu</p>

        @if ($permissions->contains('linkName', 'dashboard'))
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="nav-icon">⌂</span>Dashboard
            </a>
        @endif

        @if ($permissions->contains('linkName', 'profile'))
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                <span class="nav-icon">◉</span>My profile
            </a>
        @endif

        @if ($permissions->contains('linkName', 'mindar'))
            <a class="nav-link {{ request()->routeIs('mind-ar.index') ? 'active' : '' }}" href="{{ route('mind-ar.index') }}">
                <span class="nav-icon">◈</span>MindAR
            </a>
        @endif

        @if ($permissions->contains('linkName', 'playground'))
            <a class="nav-link {{ request()->routeIs('mind-ar.playground') ? 'active' : '' }}" href="{{ route('mind-ar.playground') }}">
                <span class="nav-icon">⌘</span>Playground
            </a>
        @endif

        @if ($permissions->contains('linkName', 'superadmin'))
            <a class="nav-link {{ request()->routeIs('superadmin.authority') ? 'active' : '' }}" href="{{ route('superadmin.authority') }}">
                <span class="nav-icon">⚙</span>Authority
            </a>
        @endif
    </nav>

    <div class="sidebar-note">Clean workspace<br>Blue light interface</div>
</aside>
