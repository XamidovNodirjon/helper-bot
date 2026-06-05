@extends('layouts.admin')

@section('title', 'Foydalanuvchilar Ro\'yxati')
@section('header_title', 'Foydalanuvchilar')
@section('header_subtitle', 'Botdan ro\'yxatdan o\'tgan jami foydalanuvchilarni qidirish, filtrlash va boshqarish')

@section('content')
    <!-- Search and Filters -->
    <form action="{{ route('admin.users') }}" method="GET" class="filter-bar glass-panel">
        <div class="form-group">
            <label for="search-input" class="form-label">Qidiruv</label>
            <input type="text" name="search" id="search-input" class="form-control" placeholder="Telegram ID yoki Username..." value="{{ request('search') }}">
        </div>

        <div class="form-group">
            <label for="filter-sub" class="form-label">Obuna holati</label>
            <select name="subscription" id="filter-sub" class="form-control">
                <option value="">Barchasi</option>
                <option value="yes" {{ request('subscription') === 'yes' ? 'selected' : '' }}>Obuna bo'lganlar</option>
                <option value="no" {{ request('subscription') === 'no' ? 'selected' : '' }}>Obuna bo'lmaganlar</option>
            </select>
        </div>

        <div class="form-group">
            <label for="filter-status" class="form-label">Hisob holati</label>
            <select name="status" id="filter-status" class="form-control">
                <option value="">Barchasi</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Faol foydalanuvchilar</option>
                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Bloklanganlar</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.25rem;">
                <i class="fa-solid fa-filter"></i>
                <span>Filtrlash</span>
            </button>
            @if(request()->anyFilled(['search', 'subscription', 'status']))
                <a href="{{ route('admin.users') }}" class="btn btn-secondary" style="padding: 0.75rem 1.25rem;">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            @endif
        </div>
    </form>

    <!-- Users Table -->
    <div class="glass-panel" style="overflow: hidden;">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Telegram ID</th>
                        <th>Username</th>
                        <th>Til</th>
                        <th>Joriy Qadam</th>
                        <th>Obuna</th>
                        <th>Holati</th>
                        <th>Qo'shilgan sana</th>
                        <th style="text-align: right;">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @if($users->isEmpty())
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                                <i class="fa-solid fa-users-slash" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                Kiritilgan filtrlarga mos keladigan foydalanuvchilar topilmadi.
                            </td>
                        </tr>
                    @else
                        @foreach($users as $user)
                            <tr>
                                <td style="font-weight: 600; color: var(--text-secondary);">#{{ $user->id }}</td>
                                <td style="font-weight: 500;"><code>{{ $user->telegram_id }}</code></td>
                                <td>
                                    @if($user->username)
                                        <a href="https://t.me/{{ $user->username }}" target="_blank" style="color: var(--color-indigo); font-weight: 500;">
                                            @/{{ $user->username }}
                                        </a>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">Mavjud emas</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="text-transform: uppercase; font-weight: 600; font-size: 0.85rem;">
                                        {{ $user->language === 'uz' ? '🇺🇿 UZ' : '🇷🇺 RU' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-warning" style="font-family: monospace;">
                                        {{ $user->step }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->is_subscribed)
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-circle-check" style="margin-right: 0.25rem;"></i> Ha
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">Yo'q</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->is_banned)
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-user-lock" style="margin-right: 0.25rem;"></i> Bloklangan
                                        </span>
                                    @else
                                        <span class="badge badge-success">Faol</span>
                                    @endif
                                </td>
                                <td style="color: var(--text-secondary); font-size: 0.85rem;">
                                    {{ $user->created_at ? $user->created_at->format('d.m.Y H:i') : '-' }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.8rem; border-radius: 6px;">
                                        <i class="fa-solid fa-user-gear"></i>
                                        <span>Boshqarish</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Jami: <b>{{ $users->total() }}</b> tadan <b>{{ $users->firstItem() }}-{{ $users->lastItem() }}</b>-foydalanuvchilar ko'rsatilmoqda
            </div>
            
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($users->onFirstPage())
                    <li class="pagination-item disabled"><span><i class="fa-solid fa-angle-left"></i></span></li>
                @else
                    <li class="pagination-item"><a href="{{ $users->previousPageUrl() }}"><i class="fa-solid fa-angle-left"></i></a></li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                    <li class="pagination-item {{ $page == $users->currentPage() ? 'active' : '' }}">
                        @if ($page == $users->currentPage())
                            <span>{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach

                {{-- Next Page Link --}}
                @if ($users->hasMorePages())
                    <li class="pagination-item"><a href="{{ $users->nextPageUrl() }}"><i class="fa-solid fa-angle-right"></i></a></li>
                @else
                    <li class="pagination-item disabled"><span><i class="fa-solid fa-angle-right"></i></span></li>
                @endif
            </ul>
        </div>
    @endif
@endsection
