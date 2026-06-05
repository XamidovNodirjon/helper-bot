@extends('layouts.admin')

@section('title', 'Foydalanuvchi Boshqaruvi')
@section('header_title')
    {{ $user->username ? '@' . $user->username : 'ID: ' . $user->telegram_id }}
@endsection
@section('header_subtitle', 'Foydalanuvchi ma\'lumotlarini o\'zgartirish, faoliyatini kuzatish va xabar yuborish')

@section('content')
    <a href="{{ route('admin.users') }}" class="btn btn-secondary" style="margin-bottom: 2rem; padding: 0.5rem 1rem;">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Foydalanuvchilarga qaytish</span>
    </a>

    <div class="detail-grid">
        <!-- Sidebar Controls & Info -->
        <div class="detail-sidebar">
            <!-- User Overview Card -->
            <div class="glass-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-regular fa-id-card" style="color: var(--color-indigo);"></i>
                        <span>Foydalanuvchi profili</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 1.5rem;">
                        <div class="avatar" style="width: 70px; height: 70px; font-size: 1.75rem; margin-bottom: 1rem; border: 3px solid var(--border-color);">
                            {{ strtoupper(substr($user->username ?? 'U', 0, 1)) }}
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 0.25rem;">
                            {{ $user->username ? '@' . $user->username : 'Telegram User' }}
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; font-family: monospace;">ID: {{ $user->telegram_id }}</p>
                    </div>

                    <div class="filters-list" style="margin-bottom: 1.5rem;">
                        <div class="filter-item">
                            <span class="filter-label">Holati:</span>
                            <span class="filter-val">
                                @if($user->is_banned)
                                    <span class="badge badge-danger">Bloklangan</span>
                                @else
                                    <span class="badge badge-success">Faol</span>
                                @endif
                            </span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">Obunasi:</span>
                            <span class="filter-val">
                                @if($user->is_subscribed)
                                    <span class="badge badge-success">Faol obunachi</span>
                                @else
                                    <span class="badge badge-secondary">Yo'q</span>
                                @endif
                            </span>
                        </div>
                        @if($user->is_subscribed && $user->subscription_expires_at)
                            <div class="filter-item">
                                <span class="filter-label">Obuna tugash sanasi:</span>
                                <span class="filter-val" style="font-size: 0.8rem; font-weight: 600;">
                                    {{ $user->subscription_expires_at->format('d.m.Y') }}
                                </span>
                            </div>
                        @endif
                        <div class="filter-item">
                            <span class="filter-label">Tanlagan tili:</span>
                            <span class="filter-val" style="text-transform: uppercase;">
                                {{ $user->language === 'uz' ? '🇺🇿 O\'zbekcha' : '🇷🇺 Русский' }}
                            </span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">Joriy qadam (Step):</span>
                            <span class="filter-val"><code>{{ $user->step }}</code></span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">Ko'rgan e'lonlari:</span>
                            <span class="filter-val">{{ $user->seenListings->count() }} ta</span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">Saqlangan qidiruvlari:</span>
                            <span class="filter-val">{{ $user->savedSearches->count() }} ta / 3 ta</span>
                        </div>
                        <div class="filter-item">
                            <span class="filter-label">Ro'yxatdan o'tgan:</span>
                            <span class="filter-val" style="font-size: 0.8rem;">
                                {{ $user->created_at ? $user->created_at->format('d.m.Y H:i') : '-' }}
                            </span>
                        </div>
                    </div>

                    <!-- Ban & Subscription Action Forms -->
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <form action="{{ route('admin.users.toggle-ban', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn {{ $user->is_banned ? 'btn-success' : 'btn-danger' }}" style="width: 100%;">
                                @if($user->is_banned)
                                    <i class="fa-solid fa-user-check"></i>
                                    <span>Blokdan chiqarish</span>
                                @else
                                    <i class="fa-solid fa-user-slash"></i>
                                    <span>Foydalanuvchini cheklash</span>
                                @endif
                            </button>
                        </form>

                        <form action="{{ route('admin.users.toggle-sub', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="width: 100%;">
                                <i class="fa-solid fa-star"></i>
                                <span>{{ $user->is_subscribed ? 'Obunani bekor qilish' : 'Obunani yoqish' }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Direct Message to User -->
            <div class="glass-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-solid fa-message" style="color: var(--color-indigo);"></i>
                        <span>Foydalanuvchiga xabar jo'natish</span>
                    </div>
                </div>
                <div class="panel-body">
                    <form action="{{ route('admin.users.message', $user->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="direct-msg" class="form-label">Xabar matni</label>
                            <textarea name="message" id="direct-msg" class="form-control" placeholder="Iltimos, bot orqali noto'g'ri so'rovlar yubormang. Yoki biron yordam kerakmi?" required style="min-height: 80px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Bot orqali yuborish</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Details Content -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Active Search Filters -->
            <div class="glass-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-solid fa-sliders" style="color: var(--color-amber);"></i>
                        <span>Foydalanuvchining so'nggi qidiruv filtrlari</span>
                    </div>
                </div>
                <div class="panel-body" style="padding: 1.25rem;">
                    @php
                        $hasFilters = $user->arenda_type || $user->region || $user->district || $user->brand || $user->price_min || $user->price_max;
                    @endphp
                    @if(!$hasFilters)
                        <p style="text-align: center; color: var(--text-muted); padding: 1rem 0;">Foydalanuvchi hali hech qanday qidiruv filterlarini tanlamagan.</p>
                    @else
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            @if($user->arenda_type)
                                <div class="filter-item">
                                    <span class="filter-label">Kategoriya:</span>
                                    <span class="filter-val" style="text-transform: capitalize;">{{ $user->arenda_type }}</span>
                                </div>
                            @endif
                            @if($user->region)
                                <div class="filter-item">
                                    <span class="filter-label">Viloyat:</span>
                                    <span class="filter-val">{{ $user->region }}</span>
                                </div>
                            @endif
                            @if($user->district)
                                <div class="filter-item">
                                    <span class="filter-label">Tuman:</span>
                                    <span class="filter-val">{{ $user->district }}</span>
                                </div>
                            @endif
                            @if($user->brand && $user->brand !== 'all')
                                <div class="filter-item">
                                    <span class="filter-label">Brend:</span>
                                    <span class="filter-val">{{ $user->brand }}</span>
                                </div>
                            @endif
                            @if($user->condition && $user->condition !== 'all')
                                <div class="filter-item">
                                    <span class="filter-label">Holati:</span>
                                    <span class="filter-val">{{ $user->condition === 'new' ? 'Yangi' : 'Ishlatilgan' }}</span>
                                </div>
                            @endif
                            @if($user->transmission && $user->transmission !== 'all')
                                <div class="filter-item">
                                    <span class="filter-label">Uzatish qutisi:</span>
                                    <span class="filter-val">{{ $user->transmission == '546' ? 'Avtomat' : 'Mexanika' }}</span>
                                </div>
                            @endif
                            @if($user->fuel_type && $user->fuel_type !== 'all')
                                <div class="filter-item">
                                    <span class="filter-label">Yoqilg'i:</span>
                                    <span class="filter-val">{{ $user->fuel_type }}</span>
                                </div>
                            @endif
                            @if($user->year_min || $user->year_max)
                                <div class="filter-item">
                                    <span class="filter-label">Yillar diapazoni:</span>
                                    <span class="filter-val">{{ $user->year_min ?? 'X' }} - {{ $user->year_max ?? 'X' }}</span>
                                </div>
                            @endif
                            @if($user->area_min || $user->area_max)
                                <div class="filter-item">
                                    <span class="filter-label">Maydon diapazoni:</span>
                                    <span class="filter-val">{{ $user->area_min ?? 'X' }} - {{ $user->area_max ?? 'X' }} m²</span>
                                </div>
                            @endif
                            @if($user->price_min || $user->price_max)
                                <div class="filter-item">
                                    <span class="filter-label">Narx diapazoni:</span>
                                    <span class="filter-val">
                                        {{ $user->price_min ? number_format($user->price_min) : 'X' }} - 
                                        {{ $user->price_max ? number_format($user->price_max) : 'X' }} 
                                        {{ $user->price_currency }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Saved Searches Profiles -->
            <div class="glass-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-regular fa-folder-open" style="color: var(--color-indigo);"></i>
                        <span>Saqlangan qidiruv profillari (Obunalar)</span>
                    </div>
                </div>
                <div class="panel-body" style="padding: 1.25rem;">
                    @if($user->savedSearches->isEmpty())
                        <p style="text-align: center; color: var(--text-muted); padding: 1rem 0;">Foydalanuvchida hech qanday saqlangan qidiruvlar mavjud emas.</p>
                    @else
                        <div class="saved-searches-list">
                            @foreach($user->savedSearches as $search)
                                <div class="saved-search-card">
                                    <div class="saved-search-header">
                                        <span class="saved-search-name">{{ $search->name }}</span>
                                        <span class="badge {{ $search->is_subscribed ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $search->is_subscribed ? 'Xabarnoma Yoqilgan' : 'Xabarnoma O\'chirilgan' }}
                                        </span>
                                    </div>
                                    <div class="saved-search-meta">
                                        <span><b>Kategoriya:</b> {{ $search->category }}</span> | 
                                        <span><b>Viloyat:</b> {{ $search->region }}</span>
                                        @if($search->district)
                                            | <span><b>Tuman:</b> {{ $search->district }}</span>
                                        @endif
                                    </div>
                                    @if($search->filters)
                                        <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem; background: rgba(0, 0, 0, 0.15); padding: 0.5rem; border-radius: 6px;">
                                            @php
                                                $f = is_array($search->filters) ? $search->filters : json_decode($search->filters, true);
                                            @endphp
                                            @if($f)
                                                @foreach($f as $k => $v)
                                                    @if($v !== null && $v !== 'all')
                                                        <span style="margin-right: 0.75rem;"><b>{{ $k }}:</b> {{ is_array($v) ? json_encode($v) : $v }}</span>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Activity Logs Timeline -->
            <div class="glass-panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-solid fa-timeline" style="color: var(--color-violet);"></i>
                        <span>Foydalanish tarixi (Harakatlar logi)</span>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="timeline-container" style="max-height: 500px;">
                        @if($logs->isEmpty())
                            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Hozircha hech qanday harakatlar qayd etilmagan.</p>
                        @else
                            <div class="timeline">
                                @foreach($logs as $log)
                                    <div class="timeline-item {{ $log->action }}">
                                        <div class="timeline-marker"></div>
                                        <div class="timeline-header">
                                            <span class="timeline-username" style="font-size: 0.85rem; color: var(--color-indigo); text-transform: uppercase;">
                                                {{ str_replace('_', ' ', $log->action) }}
                                            </span>
                                            <span class="timeline-time" style="font-size: 0.8rem;">{{ $log->created_at->format('d.m.Y H:i:s') }} ({{ $log->created_at->diffForHumans() }})</span>
                                        </div>
                                        <div class="timeline-content" style="font-size: 0.85rem;">
                                            {{ $log->details }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
