import React, { useEffect, useState } from 'react';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';
import { usePermissions } from '../hooks/usePermissions';
import { debug } from 'node:util';

interface Order {
  id: number;
  client_id: number;
  location_id: number;
  truck_id: number | null;
  driver_id: number | null;
  fuel_liters: number;
  status: string;
  window_start: string | null;
  window_end: string | null;
  delivered_liters: number | null;
  client?: { name: string };
  location?: { address: string };
  truck?: { plate_no: string };
  driver?: { name: string };
}

interface Client {
  id: number;
  name: string;
}

interface Location {
  id: number;
  client_id: number;
  address: string;
}

interface Truck {
  id: number;
  plate_no: string;
}

interface User {
  id: number;
  name: string;
  role: string;
}

export default function OrdersPage() {
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const { can } = usePermissions();
  const [orders, setOrders] = useState<Order[]>([]);
  const [clients, setClients] = useState<Client[]>([]);
  const [locations, setLocations] = useState<Location[]>([]);
  const [trucks, setTrucks] = useState<Truck[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingOrder, setEditingOrder] = useState<Order | null>(null);
  const [formData, setFormData] = useState({
    client_id: '',
    location_id: '',
    fuel_liters: '',
    window_start: '',
    window_end: '',
  });
  const [actionData, setActionData] = useState<any>({});

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {

    try {
      const [ordersRes, clientsRes, trucksRes] = await Promise.all([
        api.get<ApiResponse<Order[]>>('/orders'),
        api.get<ApiResponse<Client[]>>('/clients'),
        api.get<ApiResponse<Truck[]>>('/trucks?active_only=1'),
      ]);

      setOrders(ordersRes.data.data);
      setClients(clientsRes.data.data);
      setTrucks(trucksRes.data.data);
    } catch (error) {
      console.error('Failed to fetch data:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchLocations = async (clientId: string) => {
    try {
      const response = await api.get<ApiResponse<Location[]>>(`/locations?client_id=${clientId}`);
      setLocations(response.data.data);
    } catch (error) {
      console.error('Failed to fetch locations:', error);
    }
  };

  const handleClientChange = (clientId: string) => {
    setFormData({ ...formData, client_id: clientId, location_id: '' });
    if (clientId) {
      fetchLocations(clientId);
    } else {
      setLocations([]);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingOrder) {
        await api.put(`/orders/${editingOrder.id}`, formData);
      } else {
        await api.post('/orders', formData);
      }
      fetchData();
      resetForm();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Operation failed');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await api.delete(`/orders/${id}`);
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Delete failed');
    }
  };

  const handleEdit = (order: Order) => {
    setEditingOrder(order);
    setFormData({
      client_id: order.client_id.toString(),
      location_id: order.location_id.toString(),
      fuel_liters: order.fuel_liters.toString(),
      window_start: order.window_start ? order.window_start.slice(0, 16) : '',
      window_end: order.window_end ? order.window_end.slice(0, 16) : '',
    });
    fetchLocations(order.client_id.toString());
    setShowForm(true);
  };

  const resetForm = () => {
    setFormData({client_id: '', location_id: '', fuel_liters: '', window_start: '', window_end: ''});
    setEditingOrder(null);
    setShowForm(false);
    setLocations([]);
  };

  // Lifecycle Actions
  const handleSubmitOrder = async (orderId: number) => {
    try {
      await api.post(`/orders/${orderId}/submit`);
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Submit failed');
    }
  };

  const handleSchedule = async (orderId: number) => {
    const truckId = prompt('Enter Truck ID:');
    if (!truckId) return;
    try {
      await api.post(`/orders/${orderId}/schedule`, { truck_id: parseInt(truckId) });
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Schedule failed');
    }
  };

  const handleDispatch = async (orderId: number) => {
    const driverId = prompt('Enter Driver User ID:');
    if (!driverId) return;
    try {
      await api.post(`/orders/${orderId}/dispatch`, { driver_id: parseInt(driverId) });
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Dispatch failed');
    }
  };

  const handleDeliver = async (orderId: number) => {
    const deliveredLiters = prompt('Enter Delivered Liters:');
    if (!deliveredLiters) return;
    try {
      await api.post(`/orders/${orderId}/deliver`, { delivered_liters: parseInt(deliveredLiters) });
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Deliver failed');
    }
  };

  const handleCancel = async (orderId: number) => {
    const reason = prompt('Enter cancellation reason (optional):');
    try {
      await api.post(`/orders/${orderId}/cancel`, { reason });
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Cancel failed');
    }
  };

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      DRAFT: 'bg-gray-100 text-gray-800',
      SUBMITTED: 'bg-blue-100 text-blue-800',
      SCHEDULED: 'bg-purple-100 text-purple-800',
      EN_ROUTE: 'bg-yellow-100 text-yellow-800',
      DELIVERED: 'bg-green-100 text-green-800',
      CANCELLED: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getActions = (order: Order) => {
    const actions = [];

    if (order.status === 'DRAFT') {
      if (can('order.update')) {
        actions.push(
          <button key="edit" onClick={() => handleEdit(order)} className="text-blue-600 hover:underline mr-2">
            Edit
          </button>
        );
      }
      if (can('order.submit')) {
        actions.push(
          <button key="submit" onClick={() => handleSubmitOrder(order.id)} className="text-green-600 hover:underline mr-2">
            Submit
          </button>
        );
      }
      if (can('order.delete')) {
        actions.push(
          <button key="delete" onClick={() => handleDelete(order.id)} className="text-red-600 hover:underline mr-2">
            Delete
          </button>
        );
      }
    }

    if (order.status === 'SUBMITTED' && can('order.schedule')) {
      actions.push(
        <button key="schedule" onClick={() => handleSchedule(order.id)} className="text-purple-600 hover:underline mr-2">
          Schedule
        </button>
      );
    }

    if (order.status === 'SCHEDULED' && can('order.dispatch')) {
      actions.push(
        <button key="dispatch" onClick={() => handleDispatch(order.id)} className="text-orange-600 hover:underline mr-2">
          Dispatch
        </button>
      );
    }

    if (order.status === 'EN_ROUTE' && can('order.deliver')) {
      actions.push(
        <button key="deliver" onClick={() => handleDeliver(order.id)} className="text-green-600 hover:underline mr-2">
          Deliver
        </button>
      );
    }

    if (order.status !== 'DELIVERED' && order.status !== 'CANCELLED' && can('order.cancel')) {
      actions.push(
        <button key="cancel" onClick={() => handleCancel(order.id)} className="text-red-600 hover:underline">
          Cancel
        </button>
      );
    }

    return actions;
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Orders</h1>
        {can('order.create') && (
          <button
            onClick={() => setShowForm(!showForm)}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            {showForm ? 'Cancel' : 'Create Draft Order'}
          </button>
        )}
      </div>

      {showForm && (
        <div className="bg-white p-6 rounded-lg shadow mb-6">
          <h2 className="text-xl font-bold mb-4">
            {editingOrder ? 'Edit Order' : 'New Draft Order'}
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block mb-1 font-medium">Client *</label>
              <select
                value={formData.client_id}
                onChange={(e) => handleClientChange(e.target.value)}
                required
                className="w-full px-3 py-2 border rounded"
              >
                <option value="">Select a client</option>
                {clients.map((client) => (
                  <option key={client.id} value={client.id}>
                    {client.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block mb-1 font-medium">Location *</label>
              <select
                value={formData.location_id}
                onChange={(e) => setFormData({ ...formData, location_id: e.target.value })}
                required
                disabled={!formData.client_id}
                className="w-full px-3 py-2 border rounded disabled:bg-gray-100"
              >
                <option value="">Select a location</option>
                {locations.map((location) => (
                  <option key={location.id} value={location.id}>
                    {location.address}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block mb-1 font-medium">Fuel Liters *</label>
              <input
                type="number"
                value={formData.fuel_liters}
                onChange={(e) => setFormData({ ...formData, fuel_liters: e.target.value })}
                required
                min="100"
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block mb-1 font-medium">Window Start</label>
                <input
                  type="datetime-local"
                  value={formData.window_start}
                  onChange={(e) => setFormData({ ...formData, window_start: e.target.value })}
                  className="w-full px-3 py-2 border rounded"
                />
              </div>
              <div>
                <label className="block mb-1 font-medium">Window End</label>
                <input
                  type="datetime-local"
                  value={formData.window_end}
                  onChange={(e) => setFormData({ ...formData, window_end: e.target.value })}
                  className="w-full px-3 py-2 border rounded"
                />
              </div>
            </div>
            <div className="flex gap-2">
              <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                {editingOrder ? 'Update' : 'Create Draft'}
              </button>
              <button
                type="button"
                onClick={resetForm}
                className="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liters</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
              <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {orders.map((order) => (
              <tr key={order.id}>
                <td className="px-4 py-3">{order.id}</td>
                <td className="px-4 py-3">{order.client?.name || 'N/A'}</td>
                <td className="px-4 py-3">{order.location?.address || 'N/A'}</td>
                <td className="px-4 py-3">{order.fuel_liters.toLocaleString()}L</td>
                <td className="px-4 py-3">
                  <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(order.status)}`}>
                    {order.status}
                  </span>
                </td>
                <td className="px-4 py-3">{order.truck?.plate_no || '-'}</td>
                <td className="px-4 py-3">{getActions(order)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {orders.length === 0 && (
          <div className="p-8 text-center text-gray-500">No orders found</div>
        )}
      </div>
    </div>
  );
}
