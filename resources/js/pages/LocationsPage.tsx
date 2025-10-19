import React, { useEffect, useState } from 'react';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';

interface Location {
  id: number;
  client_id: number;
  address: string;
  lat: string | null;
  lng: string | null;
  client?: { name: string };
}

interface Client {
  id: number;
  name: string;
}

export default function LocationsPage() {
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const [locations, setLocations] = useState<Location[]>([]);
  const [clients, setClients] = useState<Client[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingLocation, setEditingLocation] = useState<Location | null>(null);
  const [formData, setFormData] = useState({
    client_id: '',
    address: '',
    lat: '',
    lng: '',
  });

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      const [locsRes, clientsRes] = await Promise.all([
        api.get<ApiResponse<Location[]>>('/locations'),
        api.get<ApiResponse<Client[]>>('/clients'),
      ]);
      setLocations(locsRes.data.data);
      setClients(clientsRes.data.data);
    } catch (error) {
      console.error('Failed to fetch data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingLocation) {
        await api.put(`/locations/${editingLocation.id}`, formData);
      } else {
        await api.post('/locations', formData);
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
      await api.delete(`/locations/${id}`);
      fetchData();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Delete failed');
    }
  };

  const handleEdit = (location: Location) => {
    setEditingLocation(location);
    setFormData({
      client_id: location.client_id.toString(),
      address: location.address,
      lat: location.lat || '',
      lng: location.lng || '',
    });
    setShowForm(true);
  };

  const resetForm = () => {
    setFormData({ client_id: '', address: '', lat: '', lng: '' });
    setEditingLocation(null);
    setShowForm(false);
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Locations</h1>
        <button
          onClick={() => setShowForm(!showForm)}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {showForm ? 'Cancel' : 'Add Location'}
        </button>
      </div>

      {showForm && (
        <div className="bg-white p-6 rounded-lg shadow mb-6">
          <h2 className="text-xl font-bold mb-4">
            {editingLocation ? 'Edit Location' : 'New Location'}
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block mb-1 font-medium">Client *</label>
              <select
                value={formData.client_id}
                onChange={(e) => setFormData({ ...formData, client_id: e.target.value })}
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
              <label className="block mb-1 font-medium">Address *</label>
              <input
                type="text"
                value={formData.address}
                onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                required
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block mb-1 font-medium">Latitude</label>
                <input
                  type="number"
                  step="any"
                  value={formData.lat}
                  onChange={(e) => setFormData({ ...formData, lat: e.target.value })}
                  className="w-full px-3 py-2 border rounded"
                />
              </div>
              <div>
                <label className="block mb-1 font-medium">Longitude</label>
                <input
                  type="number"
                  step="any"
                  value={formData.lng}
                  onChange={(e) => setFormData({ ...formData, lng: e.target.value })}
                  className="w-full px-3 py-2 border rounded"
                />
              </div>
            </div>
            <div className="flex gap-2">
              <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                {editingLocation ? 'Update' : 'Create'}
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
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coordinates</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {locations.map((location) => (
              <tr key={location.id}>
                <td className="px-6 py-4">{location.client?.name || 'N/A'}</td>
                <td className="px-6 py-4">{location.address}</td>
                <td className="px-6 py-4">
                  {location.lat && location.lng ? `${location.lat}, ${location.lng}` : '-'}
                </td>
                <td className="px-6 py-4">
                  <button onClick={() => handleEdit(location)} className="text-blue-600 hover:underline mr-3">
                    Edit
                  </button>
                  <button onClick={() => handleDelete(location.id)} className="text-red-600 hover:underline">
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {locations.length === 0 && (
          <div className="p-8 text-center text-gray-500">No locations found</div>
        )}
      </div>
    </div>
  );
}
