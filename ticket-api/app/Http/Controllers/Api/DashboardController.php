<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $salesToday = Order::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('total');

        $salesMonth = Order::where('status', 'paid')
            ->where('paid_at', '>=', $monthStart)
            ->sum('total');

        $activeEvents = Event::where('status', 'published')->count();
        $ticketsSold = Ticket::count();
        $totalUsers = User::where('role', 'user')->count();
        $totalRevenue = Order::where('status', 'paid')->sum('total');

        $salesByCategory = DB::table('tickets')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->join('categories', 'events.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(tickets.price) as total'), DB::raw('COUNT(tickets.id) as tickets_count'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $dailySales = Order::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(paid_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topEvents = DB::table('tickets')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->select('events.id', 'events.title', DB::raw('COUNT(tickets.id) as tickets_sold'), DB::raw('SUM(tickets.price) as revenue'))
            ->groupBy('events.id', 'events.title')
            ->orderByDesc('tickets_sold')
            ->take(5)
            ->get();

        return response()->json([
            'sales_today' => (float) $salesToday,
            'sales_month' => (float) $salesMonth,
            'active_events' => $activeEvents,
            'tickets_sold' => $ticketsSold,
            'total_users' => $totalUsers,
            'total_revenue' => (float) $totalRevenue,
            'sales_by_category' => $salesByCategory,
            'daily_sales' => $dailySales,
            'top_events' => $topEvents,
        ]);
    }

    public function sales(Request $request)
    {
        $period = $request->get('period', 'month');

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $orders = Order::where('status', 'paid')
            ->where('paid_at', '>=', $startDate)
            ->orderByDesc('paid_at')
            ->with('user:id,name,email')
            ->paginate(20);

        return response()->json($orders);
    }
}
