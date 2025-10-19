<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Orders\Repositories\OrderRepository;
use App\Modules\Trucks\Repositories\TruckRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private TruckRepository $truckRepository
    ) {}

    /**
     * Get order statistics
     */
    public function getOrderStatistics(string $period = 'today'): array
    {
        $dateFrom = match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => Carbon::today(),
        };

        $orders = $this->orderRepository->getOrdersSince($dateFrom);

        return [
            'total' => $orders->count(),
            'by_status' => [
                'DRAFT' => $orders->where('status', 'DRAFT')->count(),
                'SUBMITTED' => $orders->where('status', 'SUBMITTED')->count(),
                'SCHEDULED' => $orders->where('status', 'SCHEDULED')->count(),
                'EN_ROUTE' => $orders->where('status', 'EN_ROUTE')->count(),
                'DELIVERED' => $orders->where('status', 'DELIVERED')->count(),
                'CANCELLED' => $orders->where('status', 'CANCELLED')->count(),
            ],
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
        ];
    }

    /**
     * Get fleet utilization statistics
     */
    public function getFleetStatistics(): array
    {
        $allTrucks = $this->truckRepository->all();
        $activeTrucks = $allTrucks->where('active', true);

        // Get currently in-use trucks (has SCHEDULED or EN_ROUTE order)
        $inUseTrucks = $this->orderRepository->getTrucksInUse();

        $totalCapacity = $activeTrucks->sum('tank_capacity_l');

        // Get current scheduled/en_route orders' total fuel
        $currentOrders = $this->orderRepository->getCurrentActiveOrders();
        $usedCapacity = $currentOrders->sum('fuel_liters');

        return [
            'total_trucks' => $allTrucks->count(),
            'active_trucks' => $activeTrucks->count(),
            'in_use_trucks' => $inUseTrucks->count(),
            'total_capacity_liters' => $totalCapacity,
            'used_capacity_liters' => $usedCapacity,
            'capacity_utilization_percent' => $totalCapacity > 0
                ? round(($usedCapacity / $totalCapacity) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get recent activity (last 10 orders)
     */
    public function getRecentActivity(): array
    {
        $recentOrders = $this->orderRepository->getRecent(10);

        return $recentOrders->map(function ($order) {
            return [
                'id' => $order->id,
                'client_name' => $order->client?->name ?? 'N/A',
                'status' => $order->status,
                'fuel_liters' => $order->fuel_liters,
                'updated_at' => $order->updated_at->diffForHumans(),
            ];
        })->toArray();
    }
}
