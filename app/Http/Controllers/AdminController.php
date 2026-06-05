<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Models\TelegramUserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class AdminController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    /**
     * Handle authentication.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard')->with('success', 'Xush kelibsiz!');
        }

        return back()->withErrors([
            'email' => 'Kiritilgan ma\'lumotlar xato.',
        ])->onlyInput('email');
    }

    /**
     * Log the admin out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Admin Dashboard view with statistics and quick actions.
     */
    public function dashboard()
    {
        $totalUsers = TelegramUser::count();
        $activeUsersToday = TelegramUserLog::whereDate('created_at', today())
            ->distinct('telegram_user_id')
            ->count('telegram_user_id');
        $subscribedUsers = TelegramUser::where('is_subscribed', true)->count();
        $bannedUsers = TelegramUser::where('is_banned', true)->count();

        // Recent 10 activity logs
        $recentLogs = TelegramUserLog::with('user')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // 7 Day activity stats for simple chart data
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $label = now()->subDays($i)->format('d-M');
            $count = TelegramUserLog::whereDate('created_at', $date)->count();
            $searches = TelegramUserLog::whereDate('created_at', $date)
                ->where('action', 'search_executed')
                ->count();
            
            $chartData[] = [
                'label' => $label,
                'actions' => $count,
                'searches' => $searches,
            ];
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsersToday',
            'subscribedUsers',
            'bannedUsers',
            'recentLogs',
            'chartData'
        ));
    }

    /**
     * Display a paginated list of bot users.
     */
    public function usersList(Request $request)
    {
        $query = TelegramUser::query();

        // Search username or telegram ID
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('telegram_id', 'like', "%{$search}%");
            });
        }

        // Filter by subscription status
        if ($request->filled('subscription')) {
            $query->where('is_subscribed', $request->input('subscription') === 'yes');
        }

        // Filter by ban status
        if ($request->filled('status')) {
            $query->where('is_banned', $request->input('status') === 'banned');
        }

        $users = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('admin.users', compact('users'));
    }

    /**
     * User details and specific monitoring page.
     */
    public function userDetail($id)
    {
        $user = TelegramUser::with(['savedSearches', 'seenListings'])->findOrFail($id);

        // Fetch recent 100 logs for timeline
        $logs = TelegramUserLog::where('telegram_user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return view('admin.user_detail', compact('user', 'logs'));
    }

    /**
     * Toggle the banned status of a bot user.
     */
    public function toggleBan($id)
    {
        $user = TelegramUser::findOrFail($id);
        $user->is_banned = !$user->is_banned;
        $user->save();

        $statusMsg = $user->is_banned 
            ? "Foydalanuvchi muvaffaqiyatli bloklandi!" 
            : "Foydalanuvchi blokdan chiqarildi!";

        // Log this administration action
        $adminEmail = Auth::user()->email;
        $user->logs()->create([
            'action' => 'admin_action',
            'details' => "Admin ({$adminEmail}) changed ban status to: " . ($user->is_banned ? 'Banned' : 'Active'),
        ]);

        return back()->with('success', $statusMsg);
    }

    /**
     * Toggle the subscription status of a bot user.
     */
    public function toggleSubscription($id)
    {
        $user = TelegramUser::findOrFail($id);
        $user->is_subscribed = !$user->is_subscribed;
        
        if ($user->is_subscribed) {
            $user->subscription_expires_at = now()->addMonth();
        } else {
            $user->subscription_expires_at = null;
        }
        $user->save();

        $statusMsg = $user->is_subscribed 
            ? "Obuna muvaffaqiyatli yoqildi (1 oyga)!" 
            : "Obuna bekor qilindi!";

        // Log this administration action
        $adminEmail = Auth::user()->email;
        $user->logs()->create([
            'action' => 'admin_action',
            'details' => "Admin ({$adminEmail}) changed subscription status to: " . ($user->is_subscribed ? 'Subscribed' : 'Not Subscribed'),
        ]);

        return back()->with('success', $statusMsg);
    }

    /**
     * Send a direct support message from the bot to a specific user.
     */
    public function sendDirectMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = TelegramUser::findOrFail($id);

        try {
            Telegram::sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => "💬 <b>Adminstrator xabari:</b>\n\n" . $request->input('message'),
                'parse_mode' => 'HTML',
            ]);

            $adminEmail = Auth::user()->email;
            $user->logs()->create([
                'action' => 'direct_message_sent',
                'details' => "Admin ({$adminEmail}) sent direct message: " . $request->input('message'),
            ]);

            return back()->with('success', 'Xabar foydalanuvchiga muvaffaqiyatli yuborildi!');
        } catch (\Exception $e) {
            Log::error('Failed to send direct message to Telegram User: ' . $e->getMessage());
            return back()->withErrors(['message' => 'Telegram orqali xabar yuborishda xatolik yuz berdi: ' . $e->getMessage()]);
        }
    }

    /**
     * Broadcast an advertisement/news to all active bot users.
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        // Get all active users (not banned)
        $users = TelegramUser::where('is_banned', false)->get();

        if ($users->isEmpty()) {
            return back()->withErrors(['message' => 'Botda hech qanday faol foydalanuvchi mavjud emas.']);
        }

        $success = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                Telegram::sendMessage([
                    'chat_id' => $user->telegram_id,
                    'text' => $request->input('message'),
                    'parse_mode' => 'HTML',
                ]);
                
                $user->logs()->create([
                    'action' => 'broadcast_received',
                    'details' => 'Received global broadcast message',
                ]);

                $success++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning("Failed to send broadcast to telegram_id {$user->telegram_id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "E'lon yuborildi. Muvaffaqiyatli: {$success} ta, Xatolik: {$failed} ta.");
    }
}
