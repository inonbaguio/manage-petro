import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import { useTenant } from "../lib/useTenant";
import { createApiClient, ApiResponse } from "../lib/api";
import StatCard from "../components/StatCard";
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

interface DashboardStats {
  orders: {
    total: number;
    by_status: {
      DRAFT: number;
      SUBMITTED: number;
      SCHEDULED: number;
      EN_ROUTE: number;
      DELIVERED: number;
      CANCELLED: number;
    };
    period: string;
    date_from: string;
  };
  fleet: {
    total_trucks: number;
    active_trucks: number;
    in_use_trucks: number;
    total_capacity_liters: number;
    used_capacity_liters: number;
    capacity_utilization_percent: number;
  };
  recent_activity: Array<{
    id: number;
    client_name: string;
    status: string;
    fuel_liters: number;
    updated_at: string;
  }>;
}

export default function Dashboard() {
  const { user } = useAuth();
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [period, setPeriod] = useState<'today' | 'week' | 'month'>('today');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, [period]);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await api.get<ApiResponse<DashboardStats>>(`/dashboard?period=${period}`);
      setStats(response.data.data);
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading || !stats) {
    return <div className="p-8">Loading dashboard...</div>;
  }

  // Prepare chart data
  const orderStatusData = Object.entries(stats.orders.by_status).map(([status, count]) => ({
    status,
    count,
  }));

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-white p-6 rounded-lg shadow">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="text-2xl font-bold mb-2">
              Welcome, {user?.name}!
            </h2>
            <p className="text-gray-600">Tenant: <span className="font-semibold capitalize">{tenant}</span></p>
          </div>
          <div className="flex gap-2">
            <button
              onClick={() => setPeriod('today')}
              className={`px-4 py-2 rounded ${period === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-100'}`}
            >
              Today
            </button>
            <button
              onClick={() => setPeriod('week')}
              className={`px-4 py-2 rounded ${period === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100'}`}
            >
              This Week
            </button>
            <button
              onClick={() => setPeriod('month')}
              className={`px-4 py-2 rounded ${period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100'}`}
            >
              This Month
            </button>
          </div>
        </div>
      </div>

      {/* Order Statistics */}
      <div>
        <h3 className="text-lg font-semibold mb-4">Order Statistics ({period})</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            title="Total Orders"
            value={stats.orders.total}
            icon="📦"
            color="blue"
          />
          <StatCard
            title="Pending Orders"
            value={stats.orders.by_status.SUBMITTED + stats.orders.by_status.SCHEDULED}
            subtitle="Submitted + Scheduled"
            icon="⏳"
            color="orange"
          />
          <StatCard
            title="In Transit"
            value={stats.orders.by_status.EN_ROUTE}
            icon="🚛"
            color="purple"
          />
          <StatCard
            title="Delivered"
            value={stats.orders.by_status.DELIVERED}
            icon="✅"
            color="green"
          />
        </div>
      </div>

      {/* Fleet Statistics */}
      <div>
        <h3 className="text-lg font-semibold mb-4">Fleet Utilization</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <StatCard
            title="Active Trucks"
            value={`${stats.fleet.in_use_trucks} / ${stats.fleet.active_trucks}`}
            subtitle={`${stats.fleet.total_trucks} total trucks`}
            icon="🚚"
            color="blue"
          />
          <StatCard
            title="Capacity Utilization"
            value={`${stats.fleet.capacity_utilization_percent}%`}
            subtitle={`${stats.fleet.used_capacity_liters.toLocaleString()}L / ${stats.fleet.total_capacity_liters.toLocaleString()}L`}
            icon="⚡"
            color="purple"
          />
          <StatCard
            title="Available Trucks"
            value={stats.fleet.active_trucks - stats.fleet.in_use_trucks}
            subtitle="Ready for dispatch"
            icon="🟢"
            color="green"
          />
        </div>
      </div>

      {/* Orders by Status Chart */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-4">Orders by Status</h3>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={orderStatusData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="status" />
            <YAxis />
            <Tooltip />
            <Legend />
            <Bar dataKey="count" fill="#3b82f6" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Recent Activity */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-semibold mb-4">Recent Activity</h3>
        <div className="space-y-3">
          {stats.recent_activity.length > 0 ? (
            stats.recent_activity.map((activity) => (
              <div key={activity.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div className="flex-1">
                  <p className="font-medium">Order #{activity.id} - {activity.client_name}</p>
                  <p className="text-sm text-gray-500">
                    {activity.fuel_liters.toLocaleString()}L - Updated {activity.updated_at}
                  </p>
                </div>
                <span
                  className={`px-3 py-1 rounded text-xs font-medium ${
                    activity.status === 'DELIVERED' ? 'bg-green-100 text-green-800' :
                    activity.status === 'EN_ROUTE' ? 'bg-yellow-100 text-yellow-800' :
                    activity.status === 'SCHEDULED' ? 'bg-purple-100 text-purple-800' :
                    activity.status === 'SUBMITTED' ? 'bg-blue-100 text-blue-800' :
                    activity.status === 'CANCELLED' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  }`}
                >
                  {activity.status}
                </span>
              </div>
            ))
          ) : (
            <p className="text-center text-gray-500 py-4">No recent activity</p>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h3 className="text-lg font-medium mb-4">Quick Actions</h3>
        <div className="grid grid-cols-4 gap-4">
          <Link
            to={`/${tenant}/clients`}
            className="p-4 bg-blue-50 hover:bg-blue-100 rounded-lg text-center transition"
          >
            <div className="text-3xl mb-2">👥</div>
            <div className="font-medium">Manage Clients</div>
          </Link>
          <Link
            to={`/${tenant}/locations`}
            className="p-4 bg-green-50 hover:bg-green-100 rounded-lg text-center transition"
          >
            <div className="text-3xl mb-2">📍</div>
            <div className="font-medium">Manage Locations</div>
          </Link>
          <Link
            to={`/${tenant}/trucks`}
            className="p-4 bg-purple-50 hover:bg-purple-100 rounded-lg text-center transition"
          >
            <div className="text-3xl mb-2">🚛</div>
            <div className="font-medium">Manage Trucks</div>
          </Link>
          <Link
            to={`/${tenant}/orders`}
            className="p-4 bg-orange-50 hover:bg-orange-100 rounded-lg text-center transition"
          >
            <div className="text-3xl mb-2">📦</div>
            <div className="font-medium">Manage Orders</div>
          </Link>
        </div>
      </div>
    </div>
  );
}
