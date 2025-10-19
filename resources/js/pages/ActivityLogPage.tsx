import React, { useEffect, useState } from 'react';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';
import { usePermissions } from '../hooks/usePermissions';
import { useNavigate } from 'react-router-dom';

interface ActivityLog {
  id: number;
  user_id: number;
  model_type: string;
  model_id: number;
  action: string;
  old_values: Record<string, any> | null;
  new_values: Record<string, any> | null;
  description: string | null;
  ip_address: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

export default function ActivityLogPage() {
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const { can, isAdmin } = usePermissions();
  const navigate = useNavigate();
  const [logs, setLogs] = useState<ActivityLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    model_type: '',
    action: '',
    user_id: '',
    date_from: '',
    date_to: '',
  });

  // Redirect if not admin
  useEffect(() => {
    if (!isAdmin) {
      navigate(`/${tenant}`);
    }
  }, [isAdmin, tenant, navigate]);

  useEffect(() => {
    if (isAdmin) {
      fetchLogs();
    }
  }, [isAdmin]);

  const fetchLogs = async () => {
    try {
      // Build query params
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([key, value]) => {
        if (value) {
          params.append(key, value);
        }
      });

      const response = await api.get<ApiResponse<ActivityLog[]>>(
        `/activity-logs?${params.toString()}`
      );
      setLogs(response.data.data);
    } catch (error: any) {
      console.error('Failed to fetch activity logs:', error);
      if (error.response?.status === 403) {
        navigate(`/${tenant}`);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleApplyFilters = () => {
    fetchLogs();
  };

  const handleClearFilters = () => {
    setFilters({
      model_type: '',
      action: '',
      user_id: '',
      date_from: '',
      date_to: '',
    });
    setTimeout(() => fetchLogs(), 100);
  };

  const getActionColor = (action: string) => {
    const colors: Record<string, string> = {
      created: 'bg-green-100 text-green-800',
      updated: 'bg-blue-100 text-blue-800',
      deleted: 'bg-red-100 text-red-800',
      submitted: 'bg-purple-100 text-purple-800',
      scheduled: 'bg-indigo-100 text-indigo-800',
      en_route: 'bg-yellow-100 text-yellow-800',
      delivered: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
    };
    return colors[action.toLowerCase()] || 'bg-gray-100 text-gray-800';
  };

  const formatModelType = (modelType: string) => {
    return modelType.split('\\').pop() || modelType;
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  if (!isAdmin) {
    return null;
  }

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <h1 className="text-3xl font-bold mb-6">Activity Logs</h1>

      {/* Filters */}
      <div className="bg-white p-6 rounded-lg shadow mb-6">
        <h2 className="text-xl font-semibold mb-4">Filters</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <div>
            <label className="block text-sm font-medium mb-1">Model Type</label>
            <input
              type="text"
              value={filters.model_type}
              onChange={(e) => setFilters({ ...filters, model_type: e.target.value })}
              placeholder="e.g., Order"
              className="w-full px-3 py-2 border rounded"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Action</label>
            <select
              value={filters.action}
              onChange={(e) => setFilters({ ...filters, action: e.target.value })}
              className="w-full px-3 py-2 border rounded"
            >
              <option value="">All Actions</option>
              <option value="created">Created</option>
              <option value="updated">Updated</option>
              <option value="deleted">Deleted</option>
              <option value="submitted">Submitted</option>
              <option value="scheduled">Scheduled</option>
              <option value="en_route">En Route</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Date From</label>
            <input
              type="date"
              value={filters.date_from}
              onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
              className="w-full px-3 py-2 border rounded"
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">Date To</label>
            <input
              type="date"
              value={filters.date_to}
              onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
              className="w-full px-3 py-2 border rounded"
            />
          </div>

          <div className="flex items-end gap-2">
            <button
              onClick={handleApplyFilters}
              className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Apply
            </button>
            <button
              onClick={handleClearFilters}
              className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      {/* Activity Logs Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Timestamp
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                User
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Action
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Model
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Description
              </th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                IP Address
              </th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {logs.map((log) => (
              <tr key={log.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 text-xs">{formatDate(log.created_at)}</td>
                <td className="px-4 py-3">
                  {log.user?.name || 'Unknown'}
                  <br />
                  <span className="text-xs text-gray-500">{log.user?.email}</span>
                </td>
                <td className="px-4 py-3">
                  <span
                    className={`px-2 py-1 rounded text-xs font-medium ${getActionColor(
                      log.action
                    )}`}
                  >
                    {log.action.toUpperCase()}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="font-medium">{formatModelType(log.model_type)}</div>
                  <div className="text-xs text-gray-500">ID: {log.model_id}</div>
                </td>
                <td className="px-4 py-3 max-w-md">
                  {log.description || (
                    <span className="text-gray-400 italic">No description</span>
                  )}
                </td>
                <td className="px-4 py-3 text-xs text-gray-600">
                  {log.ip_address || '-'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {logs.length === 0 && (
          <div className="p-8 text-center text-gray-500">No activity logs found</div>
        )}
      </div>

      <div className="mt-4 text-sm text-gray-600">
        Showing {logs.length} activity log(s)
      </div>
    </div>
  );
}
