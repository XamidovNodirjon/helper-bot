@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Bot faoliyati va foydalanuvchilar holati haqida umumiy hisobot')

@section('content')
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card glass-panel">
            <div class="stat-header">
                <span>Jami foydalanuvchilar</span>
                <i class="fa-solid fa-users stat-icon" style="color: var(--color-indigo);"></i>
            </div>
            <div class="stat-value">{{ $totalUsers }}</div>
        </div>

        <div class="stat-card glass-panel active-users">
            <div class="stat-header">
                <span>Bugun faollar</span>
                <i class="fa-solid fa-user-check stat-icon" style="color: var(--color-amber);"></i>
            </div>
            <div class="stat-value">{{ $activeUsersToday }}</div>
        </div>

        <div class="stat-card glass-panel subscribed">
            <div class="stat-header">
                <span>Faol obunachilar</span>
                <i class="fa-solid fa-star stat-icon" style="color: var(--color-emerald);"></i>
            </div>
            <div class="stat-value">{{ $subscribedUsers }}</div>
        </div>

        <div class="stat-card glass-panel banned">
            <div class="stat-header">
                <span>Bloklanganlar</span>
                <i class="fa-solid fa-user-slash stat-icon" style="color: var(--color-rose);"></i>
            </div>
            <div class="stat-value">{{ $bannedUsers }}</div>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="glass-panel" style="margin-bottom: 2.5rem;">
        <div class="panel-header">
            <div class="panel-title">
                <i class="fa-solid fa-chart-simple" style="color: var(--color-indigo);"></i>
                <span>Oxirgi 7 kunlik foydalanuvchilar faolligi (log va qidiruvlar soni)</span>
            </div>
        </div>
        <div class="panel-body">
            <div class="chart-visualizer">
                @php
                    $maxVal = collect($chartData)->max(fn($item) => max($item['actions'], $item['searches'], 1));
                @endphp
                @foreach($chartData as $data)
                    @php
                        $actionsHeight = ($data['actions'] / $maxVal) * 100;
                        $searchesHeight = ($data['searches'] / $maxVal) * 100;
                    @endphp
                    <div class="chart-bar-wrapper">
                        <div class="chart-bar-container">
                            <!-- Actions bar -->
                            <div class="chart-bar" style="height: {{ $actionsHeight }}%;">
                                <div class="chart-bar-tooltip">
                                    Harakatlar: {{ $data['actions'] }} ta
                                </div>
                            </div>
                            <!-- Searches bar -->
                            <div class="chart-bar secondary" style="height: {{ $searchesHeight }}%;">
                                <div class="chart-bar-tooltip">
                                    Qidiruvlar: {{ $data['searches'] }} ta
                                </div>
                            </div>
                        </div>
                        <div class="chart-label">{{ $data['label'] }}</div>
                    </div>
                @endforeach
            </div>
            <div style="display: flex; gap: 2rem; margin-top: 1.25rem; font-size: 0.85rem; color: var(--text-secondary); justify-content: center;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 12px; height: 12px; border-radius: 3px; background: var(--color-indigo); display: inline-block; box-shadow: 0 0 8px var(--color-indigo);"></span>
                    <span>Jami bosilgan tugmalar va yozuvlar</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 12px; height: 12px; border-radius: 3px; background: var(--color-amber); display: inline-block; box-shadow: 0 0 8px var(--color-amber);"></span>
                    <span>Skraping / Qidiruvlar soni</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions and Live Logs Row -->
    <div class="dashboard-grid">
        <!-- Broadcast Sender Panel -->
        <div class="glass-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-bullhorn" style="color: var(--color-indigo);"></i>
                    <span>Botga reklama yuborish (Broadcast)</span>
                </div>
            </div>
            <div class="panel-body">
                <form action="{{ route('admin.broadcast') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="broadcast-msg" class="form-label">Xabar matni (HTML format qo'llab-quvvatlanadi)</label>
                        <textarea name="message" id="broadcast-msg" class="form-control" placeholder="<b>Yangi reklama!</b> 🚀&#10;&#10;Botda yangi qulayliklar qo'shildi. Batafsil ma'lumot olish uchun /start buyrug'ini bosing." required></textarea>
                        <small style="display: block; margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.8rem;">
                            E'lon botdagi barcha faol (bloklanmagan) foydalanuvchilarga yuboriladi. formatting teglaridan (<code>&lt;b&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;a href=&quot;...&quot;&gt;</code>, <code>&lt;code&gt;</code>) foydalanishingiz mumkin.
                        </small>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Barchaga jo'natish</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Live Activity Logs Panel -->
        <div class="glass-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fa-solid fa-wave-square" style="color: var(--color-violet);"></i>
                    <span>So'nggi faolliklar (Real-time logs)</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="timeline-container">
                    @if($recentLogs->isEmpty())
                        <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Hozircha hech qanday harakatlar qayd etilmagan.</p>
                    @else
                        <div class="timeline">
                            @foreach($recentLogs as $log)
                                <div class="timeline-item {{ $log->action }}">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-header">
                                        <span class="timeline-username">
                                            @if($log->user)
                                                <a href="{{ route('admin.users.detail', $log->user->id) }}">
                                                    {{ $log->user->username ? '@' . $log->user->username : 'ID: ' . $log->user->telegram_id }}
                                                </a>
                                            @else
                                                Noma'lum foydalanuvchi
                                            @endif
                                        </span>
                                        <span class="timeline-time">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="timeline-content">
                                        <strong style="color: var(--color-indigo); font-size: 0.8rem; text-transform: uppercase; display: block; margin-bottom: 0.15rem;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </strong>
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
@endsection
