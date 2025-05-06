<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Purchase;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $now = Carbon::now();

        // Penjualan hari ini
        $todaySales = Purchase::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->sum('total_price');

        // Penjualan bulan ini
        $monthlySales = Purchase::where('user_id', $userId)
            ->whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->sum('total_price');

        // Pendapatan minggu ini
        $weekStart = $now->copy()->startOfWeek();
        $weeklySales = Purchase::where('user_id', $userId)
            ->whereBetween('created_at', [$weekStart, $now])
            ->sum('total_price');

        // Jumlah transaksi hari ini
        $todayTransactions = Purchase::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->count();

        // Rata-rata nilai transaksi
        $averageOrderValue = $todayTransactions > 0 
            ? round($todaySales / $todayTransactions, 2) 
            : 0;

        // Produk dengan stok rendah
        $lowStockCount = Product::where('user_id', $userId)
            ->where('stock', '<', 3)
            ->where('is_visible', true)
            ->count();

        // Metode pembayaran paling populer hari ini
        $topPaymentMethod = Purchase::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->first();

        $popularPayment = $topPaymentMethod ? $topPaymentMethod->payment_method : 'Cash';

        // Produk terlaris hari ini
        $topProduct = Purchase::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->select('product_name', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->first();

        $topProductName = $topProduct ? $topProduct->product_name : '-';
        $topProductQty = $topProduct ? $topProduct->total_qty : 0;

        // Produk aktif
        $activeProducts = Product::where('user_id', $userId)
            ->where('is_visible', true)
            ->count();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($todaySales, 0, ',', '.'))
                ->description('Total pendapatan hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Transaksi Hari Ini', $todayTransactions)
                ->description('Jumlah transaksi selesai')
                ->descriptionIcon('heroicon-m-shopping-cart'),

            Stat::make('Nilai Rata-rata Transaksi', 'Rp ' . number_format($averageOrderValue, 0, ',', '.'))
                ->description('Rata-rata per transaksi')
                ->descriptionIcon('heroicon-m-banknotes'),

            Stat::make('Pendapatan Minggu Ini', 'Rp ' . number_format($weeklySales, 0, ',', '.'))
                ->description('Sejak awal minggu')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('indigo'),

            Stat::make('Penjualan Bulan Ini', 'Rp ' . number_format($monthlySales, 0, ',', '.'))
                ->description('Total pendapatan bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Produk Terlaris', $topProductName)
                ->description("Terjual $topProductQty unit hari ini")
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),

            Stat::make('Pembayaran Populer', $popularPayment)
                ->description('Metode pembayaran terbanyak')
                ->descriptionIcon('heroicon-m-credit-card'),

            Stat::make('Stok Menipis', $lowStockCount . ' produk')
                ->description('Membutuhkan pengisian ulang')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Produk Aktif', $activeProducts)
                ->description('Tersedia di katalog')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'Dashboard Cashere';
    }

    protected function getDescription(): ?string
    {
        return Carbon::now()->format('d F Y');
    }
}
