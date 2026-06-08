@php
$isNotifications = request()->routeIs('notifications.*');
@endphp

<header class="main-header mb-4 d-flex justify-content-between align-items-center py-3 border-bottom">
    <div class="header-left">
        @yield('header_left')
        @if(!View::hasSection('header_left'))
        <h4 class="mb-0">
            @if(Auth::user()->isAdmin())
            Admin Panel
            @elseif(Auth::user()->account_type === 'reseller_agent')
            Reseller Agent Dashboard
            @elseif(Auth::user()->account_type === 'manager')
            Manager Dashboard
            @elseif(Auth::user()->account_type === 'support_team')
            Support Team Dashboard
            @elseif(Auth::user()->account_type === 'agent')
            Agent Dashboard
            @elseif(Auth::user()->account_type === 'student')
            Student Dashboard
            @else
            Dashboard
            @endif
        </h4>
        @endif
    </div>


    <div class="header-right d-flex align-items-center gap-3">
        @if(session()->has('impersonator_id'))
        <div class="impersonation-notice me-2">
            <a href="{{ route('users.stop-impersonating') }}" class="btn btn-danger btn-sm fw-bold">
                <i class="fas fa-user-shield me-1"></i> Back to Admin
            </a>
        </div>
        @endif

        @if(session('api_error'))
        <div class="alert alert-warning p-1 px-2 m-0 small" style="font-size: 0.7rem;">
            <i class="fas fa-exclamation-triangle"></i> {{ session('api_error') }}
        </div>
        @endif

        <div class="notification-bell position-relative">
            <a href="{{ route('notifications.index') }}" class="text-decoration-none nav-link {{ $isNotifications ? 'active' : '' }}" data-toggle="Notifications" title="Notifications" id="notification-bell-btn">
                <i class="{{ $isNotifications ? 'fas' : 'far' }} fa-bell" style="font-size: 1.2rem; color: {{ $isNotifications ? '#23AAE2' : '#666' }}; cursor: pointer;"></i>
                @php
                // Always show 0 count if we are on the notifications page
                $unreadCount = request()->routeIs('notifications.index') ? 0 : auth()->user()->unreadNotifications->count();
                @endphp
                @if($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                    {{ $unreadCount }}
                    <span class="visually-hidden">unread notifications</span>
                </span>
                @endif
            </a>
        </div>

        @push('scripts')
        <script>
            document.getElementById('notification-bell-btn')?.addEventListener('click', function() {
                const badge = this.querySelector('.notification-badge');
                if (badge) {
                    badge.style.display = 'none';
                }
            });
        </script>
        @endpush

        <div class="d-flex align-items-center gap-2" id="header-country-container">
            <!-- @if(Auth::user()->country_iso)
            <img src="https://flagsapi.com/{{ strtoupper(Auth::user()->country) }}/flat/64.png" alt="{{ Auth::user()->country }}" style="width:30px;border-radius:2px;">
            @endif -->
            <span class="text-muted small fw-bold">{{ Auth::user()->country }}</span>
        </div>


        <div class="user-dropdown d-flex align-items-center gap-2">
            <img src="{{ Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : asset('images/user.png') }}" class="user-avatar rounded-circle" width="40" height="40" style="object-fit: cover;">
            <div class="user-info d-flex flex-column">
                <span class="user-name fw-bold" style="font-size: 0.9rem; line-height: 1;">{{ Auth::user()->name }}</span>
                <small class="user-role text-muted" style="font-size: 0.75rem;">
                    @if(Auth::user()->isAdmin())
                    Administrator
                    @elseif(Auth::user()->account_type === 'reseller_agent' || Auth::user()->account_type === 'student'|| Auth::user()->account_type === 'agent'|| Auth::user()->account_type === 'manager' || Auth::user()->account_type === 'support_team')
                    {{ Auth::user()->user_id }}
                    @else
                    {{ ucfirst(Auth::user()->account_type) }}
                    @endif
                </small>
            </div>

            <div class="dropdown">
                <a href="#" class="text-decoration-none text-dark" data-bs-toggle="dropdown">
                    <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: #999;"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    @if(!View::hasSection('header_left'))

                    <a class="dropdown-item" href="{{ route('account.manage') }}">Manage Account</a>

                    @endif
                    <a class="dropdown-item" href="{{ route('auth.logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
                        Logout
                    </a>
                    <form id="logout-form-header" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>