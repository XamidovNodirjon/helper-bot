<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - Telegram Bot</title>
    
    <!-- Custom stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    
    <!-- Feather icons or similar symbols -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-robot"></i>
                <span>Bot Admin Panel</span>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users') || request()->routeIs('admin.users.detail') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i>
                        <span>Foydalanuvchilar</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('editor.index') }}" class="sidebar-link" target="_blank">
                        <i class="fa-solid fa-code"></i>
                        <span>Web IDE (Kodni tahrirlash)</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="admin-profile">
                    <div class="avatar">
                        {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="profile-info">
                        <h5>{{ Auth::user()->name ?? 'Administrator' }}</h5>
                        <p>{{ Auth::user()->email ?? 'admin@bot.uz' }}</p>
                    </div>
                </div>
                
                <form action="{{ route('admin.logout') }}" method="POST" id="logout-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: flex-start; padding: 0.65rem 1rem;">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Chiqish</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top bar -->
            <div class="top-bar">
                <div class="page-title">
                    <h1>@yield('header_title')</h1>
                    <p>@yield('header_subtitle')</p>
                </div>
                
                <div class="current-time" style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;">
                    <i class="fa-regular fa-clock" style="margin-right: 0.35rem;"></i>
                    <span id="time-display">{{ now()->format('H:i') }}</span>
                </div>
            </div>
            
            <!-- Session Flash Alerts -->
            @if(session('success'))
                <div class="alert alert-success glass-panel">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger glass-panel">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </main>
    </div>

    <script>
        // Real-time time display update
        function updateTime() {
            const timeDisplay = document.getElementById('time-display');
            if (timeDisplay) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                timeDisplay.textContent = `${hours}:${minutes}`;
            }
        }
        setInterval(updateTime, 10000);
    </script>
</body>
</html>
