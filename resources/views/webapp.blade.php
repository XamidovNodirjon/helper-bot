<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E'lonlar Portali</title>
    <!-- Modern Premium Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Telegram Web App JS SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- Chart.js for beautiful price statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            --card-bg: rgba(30, 41, 59, 0.45);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-green: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --accent-blue: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --accent-orange: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            --accent-purple: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            --shadow-premium: 0 10px 30px -10px rgba(0, 0, 0, 0.6);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --star-color: #fbbf24;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 16px;
            padding-bottom: 40px;
            overflow-x: hidden;
        }

        /* Glassmorphism Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            margin-bottom: 16px;
            box-shadow: var(--shadow-premium);
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(90deg, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-title p {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Interactive Filter Control Panel */
        .filter-panel {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-premium);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-row {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .search-input-wrapper {
            position: relative;
            flex-grow: 1;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 12px 16px;
            padding-left: 40px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: var(--transition-smooth);
        }

        .search-input-wrapper input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
            background: rgba(30, 41, 59, 0.8);
        }

        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
            pointer-events: none;
        }

        .sort-select {
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            transition: var(--transition-smooth);
            width: 140px;
        }

        .sort-select:focus {
            border-color: #3b82f6;
        }

        .fav-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .fav-toggle-btn.active {
            background: var(--accent-orange);
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }

        /* Analytics Panel Styles */
        .analytics-panel {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-premium);
        }

        .analytics-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .metric-card {
            background: rgba(30, 41, 59, 0.3);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 10px;
            text-align: center;
        }

        .metric-card.green { border-color: rgba(16, 185, 129, 0.3); }
        .metric-card.blue { border-color: rgba(59, 130, 246, 0.3); }
        .metric-card.purple { border-color: rgba(139, 92, 246, 0.3); }

        .metric-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .metric-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chart-container {
            position: relative;
            height: 120px;
            width: 100%;
        }

        /* Responsive Card Grid */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        /* Glassmorphic Listing Card */
        .listing-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-premium);
            transition: var(--transition-smooth);
            position: relative;
        }

        .listing-card:hover {
            transform: translateY(-4px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.15);
        }

        /* Image Container & Swipeable Gallery */
        .image-container {
            width: 100%;
            height: 190px;
            position: relative;
            background: #020617;
            overflow: hidden;
        }

        .image-slider {
            display: flex;
            width: 100%;
            height: 100%;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }

        .image-slider::-webkit-scrollbar {
            display: none;
        }

        .slide {
            flex: 0 0 100%;
            width: 100%;
            height: 100%;
            scroll-snap-align: start;
            position: relative;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Badges & Overlays */
        .price-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: var(--accent-green);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            z-index: 5;
        }

        .image-count {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            color: var(--text-primary);
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 5;
        }

        /* Styled Source Badges */
        .source-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            color: white;
            z-index: 5;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
        }

        .source-badge.olx { background: var(--accent-blue); }
        .source-badge.avto { background: var(--accent-orange); }
        .source-badge.uybor { background: var(--accent-purple); }
        .source-badge.fallback { background: rgba(100, 116, 139, 0.8); }

        /* Floating Favorite (Star) Button */
        .favorite-btn {
            position: absolute;
            bottom: 12px;
            right: 12px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 5;
            transition: var(--transition-smooth);
            outline: none;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
            background: rgba(15, 23, 42, 0.95);
        }

        .favorite-btn svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: var(--text-secondary);
            stroke-width: 2px;
            transition: var(--transition-smooth);
        }

        .favorite-btn.is-fav svg {
            fill: var(--star-color);
            stroke: var(--star-color);
            filter: drop-shadow(0 0 4px rgba(251, 191, 36, 0.6));
        }

        /* Card Content */
        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.4;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 44px;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .meta-icon {
            color: #3b82f6;
            flex-shrink: 0;
        }

        .meta-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-desc {
            font-size: 13px;
            line-height: 1.5;
            color: var(--text-secondary);
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 58px;
        }

        /* Action Buttons */
        .card-actions {
            margin-top: auto;
        }

        .btn-view {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition-smooth);
            gap: 8px;
        }

        .btn-view:hover {
            background: var(--accent-blue);
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
            transform: scale(1.01);
        }

        /* Empty State */
        .empty-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            text-align: center;
            background: var(--card-bg);
            border-radius: 24px;
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow-premium);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-container h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .empty-container p {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 300px;
        }

        .swipe-hint {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(15, 23, 42, 0.6);
            padding: 4px 6px;
            border-radius: 6px;
            font-size: 9px;
            color: var(--text-secondary);
            pointer-events: none;
            z-index: 5;
        }

        /* Image Lightbox/Popup Modal */
        .image-popup-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .popup-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: white;
            font-size: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10002;
            transition: var(--transition-smooth);
        }

        .popup-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .popup-content-container {
            position: relative;
            max-width: 90%;
            max-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .popup-img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.2s ease;
        }

        .popup-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10001;
            transition: var(--transition-smooth);
            user-select: none;
        }

        .popup-nav-btn:hover {
            background: rgba(15, 23, 42, 0.95);
            border-color: #3b82f6;
            transform: translateY(-50%) scale(1.08);
        }

        .popup-nav-btn.prev {
            left: -25px;
        }

        .popup-nav-btn.next {
            right: -25px;
        }

        @media (max-width: 600px) {
            .popup-nav-btn {
                width: 44px;
                height: 44px;
                font-size: 16px;
            }
            .popup-nav-btn.prev {
                left: 10px;
            }
            .popup-nav-btn.next {
                right: 10px;
            }
        }

        .popup-indicator {
            margin-top: 15px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.05);
            padding: 6px 14px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body>

    <header>
        <div class="header-title">
            <h1 id="page-title">E'lonlar Portali</h1>
            <p id="page-subtitle">Sizning filtrlaringiz bo'yicha e'lonlar ro'yxati</p>
        </div>
        <div class="listings-count" id="header-count">
            {{ count($listings) }} ta
        </div>
    </header>

    <!-- Filter Control Panel -->
    <div class="filter-panel">
        <div class="filter-row">
            <div class="search-input-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="search-box" placeholder="Kalit so'z bo'yicha qidiruv...">
            </div>
        </div>
        <div class="filter-row">
            <select class="sort-select" id="sort-select">
                <option value="default">Saralash</option>
                <option value="price-asc">Arzonroq 📈</option>
                <option value="price-desc">Qimmatroq 📉</option>
                <option value="title-asc">Nomi (A-Z)</option>
            </select>
            
            <button class="fav-toggle-btn" id="fav-toggle-btn">
                ⭐ Sevimlilar
            </button>
        </div>
    </div>

    <!-- Analytics Dashboard Panel -->
    <div class="analytics-panel" id="analytics-panel" style="display: none;">
        <div class="analytics-title">
            📊 Narxlar Analitikasi (UZS)
        </div>
        <div class="metrics-grid">
            <div class="metric-card green">
                <div class="metric-label">Eng Arzon</div>
                <div class="metric-value" id="stat-min">-</div>
            </div>
            <div class="metric-card blue">
                <div class="metric-label">O'rtacha</div>
                <div class="metric-value" id="stat-avg">-</div>
            </div>
            <div class="metric-card purple">
                <div class="metric-label">Eng Qimmat</div>
                <div class="metric-value" id="stat-max">-</div>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="priceChart"></canvas>
        </div>
    </div>

    @if(empty($listings))
        <div class="empty-container">
            <div class="empty-icon">🔍</div>
            <h2>E'lonlar topilmadi</h2>
            <p>Iltimos, Telegram botga qaytib qidiruv filtrlaringizni o'zgartirib ko'ring.</p>
        </div>
    @else
        <div class="grid-container" id="listings-grid">
            @foreach($listings as $index => $listing)
                @php
                    $source = strtolower($listing['source'] ?? 'olx.uz');
                    $badgeClass = 'fallback';
                    if (strpos($source, 'olx') !== false) {
                        $badgeClass = 'olx';
                    } elseif (strpos($source, 'avto') !== false) {
                        $badgeClass = 'avto';
                    } elseif (strpos($source, 'uybor') !== false) {
                        $badgeClass = 'uybor';
                    }
                @endphp
                <div class="listing-card" 
                     data-id="{{ md5($listing['url']) }}"
                     data-title="{{ strtolower($listing['title']) }}"
                     data-desc="{{ strtolower($listing['description']) }}"
                     data-price-raw="{{ $listing['price'] }}"
                     data-url="{{ $listing['url'] }}">
                     
                    <!-- Source Badge -->
                    <span class="source-badge {{ $badgeClass }}">{{ $listing['source'] ?? 'OLX.uz' }}</span>

                    <!-- Image Gallery / Slider -->
                    <div class="image-container">
                        @if(!empty($listing['images']) && count($listing['images']) > 0)
                            <div class="image-slider">
                                @foreach($listing['images'] as $img)
                                     <div class="slide" style="cursor: pointer;">
                                         <img src="{{ $img }}" alt="{{ $listing['title'] }}" loading="lazy" onclick="openImagePopup({{ json_encode($listing['images']) }}, {{ $loop->index }}, event)">
                                     </div>
                                @endforeach
                            </div>
                            <div class="image-count">1 / {{ count($listing['images']) }} rasm</div>
                            @if(count($listing['images']) > 1)
                                <div class="swipe-hint">Swipe ➡️</div>
                            @endif
                        @else
                            <div class="slide" style="display: flex; align-items: center; justify-content: center; background: #1e293b;">
                                <span style="font-size: 32px;">📷</span>
                            </div>
                            <div class="image-count">Rasm yo'q</div>
                        @endif
                        <div class="price-badge">{{ $listing['price'] }}</div>
                        
                        <!-- Floating Favorite Button -->
                        <button class="favorite-btn" onclick="toggleFavorite('{{ md5($listing['url']) }}', event)">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <h3 class="card-title" title="{{ $listing['title'] }}">{{ $listing['title'] }}</h3>
                        
                        <div class="card-meta">
                            <span class="meta-icon">📍</span>
                            <span class="meta-text" title="{{ $listing['location'] }}">{{ $listing['location'] ?: 'Manzil ko\'rsatilmagan' }}</span>
                        </div>

                        <p class="card-desc">
                            {{ $listing['description'] ?: 'Tavsif kiritilmagan.' }}
                        </p>

                        <div class="card-actions">
                            <button class="btn-view" onclick="openLink('{{ $listing['url'] }}')">
                                🔗 Saytda ko'rish
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="empty-container" id="filter-empty-state" style="display: none;">
            <div class="empty-icon">❌</div>
            <h2>Hech narsa topilmadi</h2>
            <p>Kiritilgan filtrlarga mos keladigan e'lonlar mavjud emas.</p>
        </div>
    @endif

    <script>
        // Initialize Telegram WebApp
        const webapp = window.Telegram?.WebApp;
        if (webapp) {
            webapp.ready();
            webapp.expand();
        }

        // Safe Open Link function via Telegram WebApp SDK
        function openLink(url) {
            if (webapp && typeof webapp.openLink === 'function') {
                webapp.openLink(url);
            } else {
                window.open(url, '_blank');
            }
        }

        // Horizontal scroll slide indicator
        document.querySelectorAll('.image-slider').forEach(slider => {
            slider.addEventListener('scroll', function() {
                const width = this.clientWidth;
                const scrollLeft = this.scrollLeft;
                const activeIndex = Math.round(scrollLeft / width);
                const countBadge = this.parentElement.querySelector('.image-count');
                const total = this.children.length;
                if (countBadge) {
                    countBadge.textContent = `${activeIndex + 1} / ${total} rasm`;
                }
            });
        });

        // ---------------- FAVORITES SYSTEM ----------------
        let favorites = JSON.parse(localStorage.getItem('fav_listings') || '[]');

        function saveFavorites() {
            localStorage.setItem('fav_listings', JSON.stringify(favorites));
        }

        function toggleFavorite(id, event) {
            event.stopPropagation();
            const btn = event.currentTarget;
            const index = favorites.indexOf(id);

            if (index > -1) {
                favorites.splice(index, 1);
                btn.classList.remove('is-fav');
            } else {
                favorites.push(id);
                btn.classList.add('is-fav');
            }
            saveFavorites();
            
            if (showFavoritesOnly) {
                applyFilters();
            }
        }

        // Initialize favorite button styles on load
        document.querySelectorAll('.listing-card').forEach(card => {
            const id = card.getAttribute('data-id');
            if (favorites.includes(id)) {
                card.querySelector('.favorite-btn').classList.add('is-fav');
            }
        });

        // ---------------- INTERACTIVE FILTERING & SORTING ----------------
        const searchBox = document.getElementById('search-box');
        const sortSelect = document.getElementById('sort-select');
        const favToggleBtn = document.getElementById('fav-toggle-btn');
        const listingsGrid = document.getElementById('listings-grid');
        const filterEmptyState = document.getElementById('filter-empty-state');
        const headerCount = document.getElementById('header-count');
        
        let showFavoritesOnly = false;
        let chartInstance = null;

        if (searchBox) searchBox.addEventListener('input', applyFilters);
        if (sortSelect) sortSelect.addEventListener('change', applyFilters);
        if (favToggleBtn) {
            favToggleBtn.addEventListener('click', () => {
                showFavoritesOnly = !showFavoritesOnly;
                favToggleBtn.classList.toggle('active', showFavoritesOnly);
                applyFilters();
            });
        }

        // Convert formatted price strings to numerical UZS for calculation
        function parsePriceToUzs(priceStr) {
            if (!priceStr) return null;
            let cleaned = priceStr.toLowerCase().replace(/\s/g, '').replace(/,/g, '');
            let isUsd = cleaned.includes('$') || cleaned.includes('usd');
            let isKelishiladi = cleaned.includes('kelishiladi') || cleaned.includes('договор');
            
            if (isKelishiladi) return null;

            let numbers = cleaned.replace(/[^0-9.]/g, '');
            if (!numbers) return null;
            let val = parseFloat(numbers);
            
            // Central Bank of Uzbekistan standard USD/UZS approximate rate for analytics
            if (isUsd) {
                return val * 12800; 
            }
            return val;
        }

        function formatUzs(val) {
            if (val >= 1000000) {
                return (val / 1000000).toFixed(1) + ' mln UZS';
            }
            return val.toLocaleString() + ' UZS';
        }

        function updateAnalytics(visibleCards) {
            const prices = [];
            
            visibleCards.forEach(card => {
                const priceRaw = card.getAttribute('data-price-raw');
                const priceVal = parsePriceToUzs(priceRaw);
                if (priceVal !== null && priceVal > 0) {
                    prices.push(priceVal);
                }
            });

            const panel = document.getElementById('analytics-panel');
            if (prices.length === 0) {
                panel.style.display = 'none';
                return;
            }

            panel.style.display = 'block';
            prices.sort((a, b) => a - b);
            
            const min = prices[0];
            const max = prices[prices.length - 1];
            const avg = prices.reduce((a, b) => a + b, 0) / prices.length;

            document.getElementById('stat-min').innerText = formatUzs(min);
            document.getElementById('stat-avg').innerText = formatUzs(avg);
            document.getElementById('stat-max').innerText = formatUzs(max);

            // Update Chart.js dataset
            renderPriceChart(prices);
        }

        function renderPriceChart(prices) {
            const ctx = document.getElementById('priceChart').getContext('2d');
            
            // Bucket prices into categories
            let labels = [];
            let counts = [];
            
            if (prices.length <= 5) {
                labels = prices.map((p, idx) => `E'lon #${idx + 1}`);
                counts = prices;
            } else {
                // Group into 5 price ranges
                const min = prices[0];
                const max = prices[prices.length - 1];
                const bucketSize = (max - min) / 5;
                
                labels = [];
                counts = [0, 0, 0, 0, 0];
                
                for(let i=0; i<5; i++) {
                    const start = min + (i * bucketSize);
                    const end = start + bucketSize;
                    labels.push(`${(start/1000000).toFixed(1)}-${(end/1000000).toFixed(1)}M`);
                }
                
                prices.forEach(price => {
                    let index = Math.floor((price - min) / bucketSize);
                    if (index >= 5) index = 4;
                    if (index < 0) index = 0;
                    counts[index]++;
                });
            }

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Narxlar oralig\'i',
                        data: counts,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#94a3b8', font: { size: 9 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 9 } }
                        }
                    }
                }
            });
        }

        function applyFilters() {
            if (!listingsGrid) return;

            const query = searchBox.value.toLowerCase().trim();
            const sortBy = sortSelect.value;
            const cards = Array.from(listingsGrid.getElementsByClassName('listing-card'));
            
            let visibleCards = [];

            cards.forEach(card => {
                const title = card.getAttribute('data-title');
                const desc = card.getAttribute('data-desc');
                const id = card.getAttribute('data-id');
                
                const matchesSearch = !query || title.includes(query) || desc.includes(query);
                const matchesFav = !showFavoritesOnly || favorites.includes(id);

                if (matchesSearch && matchesFav) {
                    card.style.display = 'flex';
                    visibleCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            // Sorting visible listings
            if (sortBy !== 'default') {
                visibleCards.sort((a, b) => {
                    if (sortBy === 'price-asc' || sortBy === 'price-desc') {
                        const priceA = parsePriceToUzs(a.getAttribute('data-price-raw')) || 0;
                        const priceB = parsePriceToUzs(b.getAttribute('data-price-raw')) || 0;
                        return sortBy === 'price-asc' ? priceA - priceB : priceB - priceA;
                    }
                    if (sortBy === 'title-asc') {
                        const titleA = a.getAttribute('data-title');
                        const titleB = b.getAttribute('data-title');
                        return titleA.localeCompare(titleB);
                    }
                    return 0;
                });

                // Re-append sorted cards in DOM
                visibleCards.forEach(card => listingsGrid.appendChild(card));
            }

            // Update Counters & View States
            headerCount.innerText = `${visibleCards.length} ta`;
            
            if (visibleCards.length === 0) {
                filterEmptyState.style.display = 'flex';
                listingsGrid.style.display = 'none';
            } else {
                filterEmptyState.style.display = 'none';
                listingsGrid.style.display = 'grid';
            }

            // Update charts and statistics on filter change
            updateAnalytics(visibleCards);
        }

        // Initial Analytics computation on load
        document.addEventListener('DOMContentLoaded', () => {
            const cards = Array.from(document.querySelectorAll('.listing-card') || []);
            updateAnalytics(cards);
        });

        // ---------------- IMAGE POPUP GALLERY ----------------
        let currentPopupImages = [];
        let currentPopupIndex = 0;

        function openImagePopup(images, index, event) {
            if (event) {
                event.stopPropagation();
            }
            if (!images || images.length === 0) return;
            
            currentPopupImages = images;
            currentPopupIndex = index;
            
            const modal = document.getElementById('image-popup-modal');
            updatePopupImage();
            modal.style.display = 'flex';
        }

        function closeImagePopup() {
            const modal = document.getElementById('image-popup-modal');
            modal.style.display = 'none';
        }

        function updatePopupImage() {
            const imgElement = document.getElementById('popup-img');
            const indicatorElement = document.getElementById('popup-indicator');
            
            if (currentPopupImages.length > 0) {
                imgElement.src = currentPopupImages[currentPopupIndex];
                indicatorElement.textContent = `${currentPopupIndex + 1} / ${currentPopupImages.length}`;
            }
        }

        function prevPopupImage(event) {
            if (event) event.stopPropagation();
            if (currentPopupImages.length <= 1) return;
            
            currentPopupIndex = (currentPopupIndex - 1 + currentPopupImages.length) % currentPopupImages.length;
            updatePopupImage();
        }

        function nextPopupImage(event) {
            if (event) event.stopPropagation();
            if (currentPopupImages.length <= 1) return;
            
            currentPopupIndex = (currentPopupIndex + 1) % currentPopupImages.length;
            updatePopupImage();
        }

        // Close on clicking outside the image container
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('image-popup-modal');
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        closeImagePopup();
                    }
                });
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (modal.style.display === 'flex') {
                        if (e.key === 'ArrowLeft') {
                            prevPopupImage();
                        } else if (e.key === 'ArrowRight') {
                            nextPopupImage();
                        } else if (e.key === 'Escape') {
                            closeImagePopup();
                        }
                    }
                });
            }
        });
    </script>

    <!-- Image Popup Modal Markup -->
    <div class="image-popup-modal" id="image-popup-modal">
        <button class="popup-close-btn" onclick="closeImagePopup()">×</button>
        <div class="popup-content-container">
            <button class="popup-nav-btn prev" onclick="prevPopupImage(event)">‹</button>
            <img src="" alt="Popup Image" class="popup-img" id="popup-img">
            <button class="popup-nav-btn next" onclick="nextPopupImage(event)">›</button>
        </div>
        <div class="popup-indicator" id="popup-indicator">0 / 0</div>
    </div>
</body>
</html>
