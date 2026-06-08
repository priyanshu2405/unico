<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-3 sidebar sidebar-hidden">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4 d-flex justify-content-between align-items-center px-3">
            <a class="text-start" href="/">
                <img src="/images/company_logo.png" class="dashboard-logo" alt="">
            </a>
            <button id="close-sidebar" class="btn btn-link text-dark d-lg-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <ul class="nav flex-column gap-2">

            @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isSupport() || auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())

            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_view_users))
            <!-- User Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('users.management') }}">
                    <i class="fas fa-users me-2"></i> User Management
                </a>
            </li>
            @if(auth()->user()->isAdmin() )
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('manager.page') }}">
                    <i class="fas fa-user-tie me-2"></i>Manager
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_view_users))
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.team') }}">
                    <i class="fas fa-tools me-2"></i>Support Team
                </a>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_view_users) || auth()->user()->isSupport())
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('reseller.agent') }}">
                    <i class="fas fa-handshake me-2"></i>Reseller Agent
                </a>
            </li>
            @endif
            <!-- KYC & Compliance -->
            <!-- <li class="nav-item">
                <a class="nav-link" href="{{ route('kyc.compliance') }}">
                    <i class="fas fa-id-card me-2"></i> KYC & Compliance
                </a>
            </li> -->
            @endif
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
            <!-- Pricing -->
            <!-- Inventory -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('sales.index') }}">
                    <i class="fas fa-tags me-2"></i> Sales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('inventory.index') }}">
                    <i class="fas fa-boxes me-2"></i>Stocks upload
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('pricing.index') }}">
                    <i class="fas fa-tags me-2"></i> Purches
                </a>
            </li>


            @endif

            <li class="nav-item">
                <a class="nav-link" href="{{route('bonus')}}">
                    <i class="fas fa-calendar-alt me-2"></i> Bonus Point
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{route('referral')}}">
                    <i class="fas fa-users me-2"></i> Referral Points
                </a>
            </li>
            <!-- @if(in_array(auth()->user()->account_type, ['admin', 'manager', 'reseller_agent']))
           
            <li class="nav-item">
                <a class="nav-link" href="{{ route('wallet.index') }}">
                    <i class="fas fa-wallet me-2"></i> Wallet / Store Credit
                </a>
            </li>
            @endif -->
            @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
            <!-- Wallet / Store Credit -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('profile.index') }}">
                    <i class="fas fa-wallet me-2"></i> My Profile
                </a>
            </li>
            @endif

            @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
            <!-- Linked Banks -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('bank.link') }}">
                    <i class="fas fa-university me-2"></i> Linked Banks
                </a>
            </li>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isManager() )
            <!-- Linked Banks -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('banks.bank-table') }}">
                    <i class="fas fa-university me-2"></i> Banks
                </a>
            </li>

            <!-- Admin Payment Methods -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('payment-methods.index') }}">
                    <i class="fas fa-credit-card me-2"></i> Payment Methods
                </a>
            </li>
            @endif

            <!-- @if(in_array(auth()->user()->account_type, ['admin', 'manager']))
           
            <li class="nav-item">
                <a class="nav-link" href="{{ route('vouchers') }}">
                    <i class="fas fa-ticket-alt me-2"></i> Voucher Management
                </a>
            </li>
            @endif -->

            @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('vouchers') }}">
                    <i class="fas fa-ticket-alt me-2"></i> Voucher
                </a>
            </li>
            @endif


            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_view_users) || auth()->user()->isSupport())
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('regular.agent') }}">
                    <i class="fas fa-user me-2"></i> Agent
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin() || (auth()->user()->isManager() && auth()->user()->can_view_users) || auth()->user()->isSupport())
            <!-- Voucher Management -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('student.page') }}">
                    <i class="fas fa-user-graduate me-2"></i> Student
                </a>
            </li>
            @endif
            <!-- @if(in_array(auth()->user()->account_type, ['reseller_agent', 'student']))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('agent.vouchers') }}">
                    <i class="fas fa-ticket-alt me-2"></i> My Vouchers
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-plus-circle me-2"></i> Redeem Voucher
                </a>
            </li>
            @endif -->

            @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->isSupport())
            <li class="nav-item">
                <a class="nav-link" href="{{ route('orders.index') }}">
                    <i class="fas fa-truck me-2"></i> Orders & Delivery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('refunds.index') }}">
                    <i class="fas fa-undo me-2"></i> Refund Requests
                </a>
            </li>
            @endif

            @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
            <li class="nav-item">
                <a class="nav-link" href="{{ route('orders.history') }}">
                    <i class="fas fa-shopping-cart me-2"></i> My Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('refunds.user') }}">
                    <i class="fas fa-undo me-2"></i> My Refunds
                </a>
            </li>
            @endif



            <!-- @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent()) -->
            <!-- Agent Points -->
            <!-- <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-pie me-2"></i> Quarterly Points
                </a>
            </li>

            -->

            <!-- @endif -->

            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
            <!-- Disputes -->
            <li class="nav-item">
                <a class="nav-link" href="">
                    <i class="fas fa-gavel me-2"></i> Disputes & Refunds
                </a>
            </li>



            <!-- System Control -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('system.control') }}">
                    <i class="fas fa-cogs me-2"></i> System Control
                </a>
            </li>

            <!-- Audit -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('audit.index') }}">
                    <i class="fas fa-clipboard-list me-2"></i> Audit & Logs
                </a>
            </li>

            <!-- Settings -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('settings.risk-levels') }}">
                    <i class="fas fa-globe me-2"></i> Country Risk Levels
                </a>
            </li>
            @endif
            @if(auth()->user()->isAgent() || auth()->user()->isStudent() || auth()->user()->isResellerAgent())
            <!-- Support Center -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('customer.support') }}">
                    <i class="fas fa-headset me-2"></i> Customer Support
                </a>
            </li>
            @endif
            @if(auth()->user()->isAdmin() || auth()->user()->isManager())
            <!-- Support Center -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('customer.query') }}">
                    <i class="fas fa-headset me-2"></i> Customer Query
                </a>
            </li>
            @endif
        </ul>

        <hr class="my-3" style="border-color: #495057;">

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ url('/') }}" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    View Website
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}">
                    <i class="fas fa-bell me-2"></i>
                    Notifications
                </a>
            </li>

            <li class="nav-item">
                <form action="{{ route('auth.logout') }}" method="POST" id="logout-form" style="display: none;">
                    @csrf
                </form>
                <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>