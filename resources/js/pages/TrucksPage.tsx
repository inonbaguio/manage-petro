import React, { useEffect, useState } from 'react';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';

interface Truck {
  id: number;
  plate_no: string;
  tank_capacity_l: number;
  active: boolean;
}

export default function TrucksPage() {
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const [trucks, setTrucks] = useState<Truck[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingTruck, setEditingTruck] = useState<Truck | null>(null);
  const [formData, setFormData] = useState({
    plate_no: '',
    tank_capacity_l: '',
    active: true,
  });

  useEffect(() => {
    fetchTrucks();
  }, []);

  const fetchTrucks = async () => {
    try {
      const response = await api.get<ApiResponse<Truck[]>>('/trucks');
      setTrucks(response.data.data);
    } catch (error) {
      console.error('Failed to fetch trucks:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingTruck) {
        await api.put(`/trucks/${editingTruck.id}`, formData);
      } else {
        await api.post('/trucks', formData);
      }
      fetchTrucks();
      resetForm();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Operation failed');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await api.delete(`/trucks/${id}`);
      fetchTrucks();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Delete failed');
    }
  };

  const toggleActive = async (id: number) => {
    try {
      await api.post(`/trucks/${id}/toggle-active`);
      fetchTrucks();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Toggle failed');
    }
  };

  const handleEdit = (truck: Truck) => {
    setEditingTruck(truck);
    setFormData({
      plate_no: truck.plate_no,
      tank_capacity_l: truck.tank_capacity_l.toString(),
      active: truck.active,
    });
    setShowForm(true);
  };

  const resetForm = () => {
    setFormData({ plate_no: '', tank_capacity_l: '', active: true });
    setEditingTruck(null);
    setShowForm(false);
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Trucks</h1>
        <button
          onClick={() => setShowForm(!showForm)}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {showForm ? 'Cancel' : 'Add Truck'}
        </button>
      </div>

      {showForm && (
        <div className="bg-white p-6 rounded-lg shadow mb-6">
          <h2 className="text-xl font-bold mb-4">
            {editingTruck ? 'Edit Truck' : 'New Truck'}
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block mb-1 font-medium">Plate Number *</label>
              <input
                type="text"
                value={formData.plate_no}
                onChange={(e) => setFormData({ ...formData, plate_no: e.target.value })}
                required
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div>
              <label className="block mb-1 font-medium">Tank Capacity (Liters) *</label>
              <input
                type="number"
                value={formData.tank_capacity_l}
                onChange={(e) => setFormData({ ...formData, tank_capacity_l: e.target.value })}
                required
                min="1000"
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="active"
                checked={formData.active}
                onChange={(e) => setFormData({ ...formData, active: e.target.checked })}
                className="w-4 h-4"
              />
              <label htmlFor="active" className="font-medium">Active</label>
            </div>
            <div className="flex gap-2">
              <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                {editingTruck ? 'Update' : 'Create'}
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
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plate Number</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity (L)</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {trucks.map((truck) => (
              <tr key={truck.id}>
                <td className="px-6 py-4 font-medium">{truck.plate_no}</td>
                <td className="px-6 py-4">{truck.tank_capacity_l.toLocaleString()}</td>
                <td className="px-6 py-4">
                  <span
                    className={`px-2 py-1 rounded text-xs font-medium ${
                      truck.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}
                  >
                    {truck.active ? 'Active' : 'Inactive'}
                  </span>
                </td>
                <td className="px-6 py-4">
                  <button onClick={() => handleEdit(truck)} className="text-blue-600 hover:underline mr-3">
                    Edit
                  </button>
                  <button onClick={() => toggleActive(truck.id)} className="text-purple-600 hover:underline mr-3">
                    {truck.active ? 'Deactivate' : 'Activate'}
                  </button>
                  <button onClick={() => handleDelete(truck.id)} className="text-red-600 hover:underline">
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {trucks.length === 0 && (
          <div className="p-8 text-center text-gray-500">No trucks found</div>
        )}
      </div>
    </div>
  );
}
