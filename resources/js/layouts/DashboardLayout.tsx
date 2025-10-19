import React from 'react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useTenant } from '../lib/useTenant';
import { usePermissions } from '../hooks/usePermissions';

export const DashboardLayout: React.FC = () => {
  const { user, logout } = useAuth();
  const tenant = useTenant();
  const location = useLocation();
  const { can } = usePermissions();

  const isActive = (path: string) => {
    return location.pathname.includes(path) ? 'bg-blue-700' : '';
  };

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Top Nav */}
      <nav className="bg-blue-600 text-white shadow-lg">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center space-x-8">
              <Link to={`/${tenant}/dashboard`} className="text-xl font-bold">
                Manage Petro
              </Link>
              <div className="flex space-x-1">
                <Link
                  to={`/${tenant}/dashboard`}
                  className={`px-3 py-2 rounded ${isActive('/dashboard') && !location.pathname.includes('clients') && !location.pathname.includes('locations') && !location.pathname.includes('trucks') && !location.pathname.includes('orders')}`}
                >
                  Dashboard
                </Link>
                <Link
                  to={`/${tenant}/clients`}
                  className={`px-3 py-2 rounded ${isActive('/clients')}`}
                >
                  Clients
                </Link>
                <Link
                  to={`/${tenant}/locations`}
                  className={`px-3 py-2 rounded ${isActive('/locations')}`}
                >
                  Locations
                </Link>
                <Link
                  to={`/${tenant}/trucks`}
                  className={`px-3 py-2 rounded ${isActive('/trucks')}`}
                >
                  Trucks
                </Link>
                <Link
                  to={`/${tenant}/orders`}
                  className={`px-3 py-2 rounded ${isActive('/orders')}`}
                >
                  Orders
                </Link>
                {can('activity_log.view') && (
                  <Link
                    to={`/${tenant}/activity-logs`}
                    className={`px-3 py-2 rounded ${isActive('/activity-logs')}`}
                  >
                    Activity Logs
                  </Link>
                )}
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm">
                {user?.name} ({user?.role}) - <span className="capitalize">{tenant}</span>
              </span>
              <button
                onClick={logout}
                className="px-4 py-2 bg-blue-700 rounded hover:bg-blue-800"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
};
