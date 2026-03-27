@php
    $currentUser = auth()->user();
    $notifications = admin_notifications();
    $notificationCount = count($notifications);
    $currentRole = $currentUser?->getRoleNames()->first();
    $visibleNotifications = collect($notifications)->take(5);
    $moreNotifications = collect($notifications)->slice(5);
@endphp

<header class="app-header">
    <div class="main-header-container container-fluid" style="height: 4rem;">
        <div class="header-content-left">
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ route('admin.dashboard') }}" class="header-logo">
                        <img src="{{ app_logo_url() }}" alt="logo" class="desktop-logo">
                        <img src="{{ app_logo_url() }}" alt="logo" class="toggle-logo">
                        <img src="{{ app_logo_url() }}" alt="logo" class="desktop-white">
                        <img src="{{ app_logo_url() }}" alt="logo" class="toggle-white">
                    </a>
                </div>
            </div>

            <div class="header-element">
                <a aria-label="Hide Sidebar"
                    class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle"
                    data-bs-toggle="sidebar" href="javascript:void(0);">
                    <i class="header-icon fe fe-align-left"></i>
                </a>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="header-element dropdown">
                <a href="javascript:void(0);" class="header-link position-relative" id="mainHeaderNotification"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    data-notification-user="{{ $currentUser?->id ?? 'guest' }}">
                    <i class="fe fe-bell fs-18"></i>
                    <span id="headerNotificationCount"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none notification-count-badge notification-count-pending"
                        data-total-count="{{ $notificationCount }}"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-0 header-notification-dropdown" aria-labelledby="mainHeaderNotification">
                    <li class="dropdown-header border-bottom py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center gap-2 w-100">
                            <div>
                                <strong>Notifications</strong>
                                <span class="notification-summary-line text-muted" id="notificationStateLabel">
                                    {{ $notificationCount > 0 ? 'Checking unread items...' : 'All caught up' }}
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary notification-mark-all-btn"
                                id="notificationMarkAllRead" @disabled($notificationCount === 0)>
                                Mark all read
                            </button>
                        </div>
                    </li>
                    <li>
                        <div class="header-notification-scroll" id="header-notification-scroll" data-native-scroll="true">
                            @forelse ($visibleNotifications as $notification)
                                <a href="{{ $notification['url'] }}"
                                    class="dropdown-item py-3 notification-item-card is-unread"
                                    data-notification-id="{{ $notification['id'] }}">
                                    <span class="notification-item-dot" aria-hidden="true"></span>
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-grow-1">
                                        <div class="notification-item-content">
                                            <div class="fw-semibold notification-item-title">{{ $notification['title'] }}</div>
                                            <div class="text-muted small notification-item-meta">{{ $notification['message'] }}</div>
                                        </div>
                                        <span class="badge bg-{{ $notification['color'] }}">{{ ucfirst($notification['color']) }}</span>
                                    </div>
                                </a>
                            @empty
                                <div class="dropdown-item text-muted py-3">No new notification right now.</div>
                            @endforelse

                            @foreach ($moreNotifications as $notification)
                                <a href="{{ $notification['url'] }}"
                                    class="dropdown-item py-3 notification-item-card notification-more is-unread d-none"
                                    data-notification-id="{{ $notification['id'] }}">
                                    <span class="notification-item-dot" aria-hidden="true"></span>
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-grow-1">
                                        <div class="notification-item-content">
                                            <div class="fw-semibold notification-item-title">{{ $notification['title'] }}</div>
                                            <div class="text-muted small notification-item-meta">{{ $notification['message'] }}</div>
                                        </div>
                                        <span class="badge bg-{{ $notification['color'] }}">{{ ucfirst($notification['color']) }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </li>
                    @if ($moreNotifications->isNotEmpty())
                        <li>
                            <button type="button" class="dropdown-item text-center notification-load-more-btn" id="notificationLoadMore">
                                Load more
                            </button>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="header-element headerProfile-dropdown">
                <div class="me-3">
                    <span>Welcome! </span><a href="{{ route('admin.dashboard') }}"><label>{{ $currentUser?->name }}</label></a>
                </div>
                <div>
                    <a href="{{ route('admin.profile.index') }}">
                        <img src="{{ asset('images/no-user.jpg') }}" class="rounded-circle" alt="User" width="37" height="37">
                    </a>
                </div>
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="fa-solid fa-angle-down"></i>
                </a>
                <ul class="main-header-dropdown dropdown-menu pt-0 header-profile-dropdown dropdown-menu-end main-profile-menu"
                    aria-labelledby="mainHeaderProfile">
                    <li>
                        <div class="main-header-profile bg-primary menu-header-content text-fixed-white">
                            <div class="my-auto">
                                <h6 class="mb-0 lh-1 text-fixed-white">{{ $currentUser?->name }}</h6>
                                <span class="fs-11 op-7 lh-1">{{ ucfirst($currentRole ?? 'user') }}</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex" href="{{ route('admin.profile.index') }}">
                            <i class="bx bx-user-circle fs-18 me-2 op-7"></i>Profile
                        </a>
                    </li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex border-0 bg-transparent w-100 text-start">
                                <i class="bx bx-log-out fs-18 me-2 op-7"></i>Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
