<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tizimga Kirish - Telegram Bot Admin Panel</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="login-wrapper">
        <div class="login-card glass-panel">
            <div class="login-logo">
                <i class="fa-solid fa-robot"></i> Bot Admin
            </div>
            
            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 1.5rem; font-size: 0.85rem; padding: 0.75rem 1rem;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            
            <form action="{{ url('admin/login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email" class="form-label">Email manzil</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="admin@bot.uz" required autofocus value="{{ old('email') }}">
                </div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="password" class="form-label">Maxfiy kalit (Parol)</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <span>Tizimga Kirish</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>
