import React, { useEffect, useState } from 'react';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';
import { usePermissions } from '../hooks/usePermissions';

interface Client {
  id: number;
  name: string;
  contact_person: string | null;
  contact_phone: string | null;
  contact_email: string | null;
  locations_count?: number;
}

export default function ClientsPage() {
  const tenant = useTenant();
  const api = createApiClient(tenant);
  const { can } = usePermissions();
  const [clients, setClients] = useState<Client[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingClient, setEditingClient] = useState<Client | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    contact_person: '',
    contact_phone: '',
    contact_email: '',
  });

  useEffect(() => {
    fetchClients();
  }, []);

  const fetchClients = async () => {
    try {
      const response = await api.get<ApiResponse<Client[]>>('/clients');
      setClients(response.data.data);
    } catch (error) {
      console.error('Failed to fetch clients:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingClient) {
        await api.put(`/clients/${editingClient.id}`, formData);
      } else {
        await api.post('/clients', formData);
      }
      fetchClients();
      resetForm();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Operation failed');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await api.delete(`/clients/${id}`);
      fetchClients();
    } catch (error: any) {
      alert(error.response?.data?.message || 'Delete failed');
    }
  };

  const handleEdit = (client: Client) => {
    setEditingClient(client);
    setFormData({
      name: client.name,
      contact_person: client.contact_person || '',
      contact_phone: client.contact_phone || '',
      contact_email: client.contact_email || '',
    });
    setShowForm(true);
  };

  const resetForm = () => {
    setFormData({ name: '', contact_person: '', contact_phone: '', contact_email: '' });
    setEditingClient(null);
    setShowForm(false);
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Clients</h1>
        {can('client.create') && (
          <button
            onClick={() => setShowForm(!showForm)}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            {showForm ? 'Cancel' : 'Add Client'}
          </button>
        )}
      </div>

      {showForm && (
        <div className="bg-white p-6 rounded-lg shadow mb-6">
          <h2 className="text-xl font-bold mb-4">
            {editingClient ? 'Edit Client' : 'New Client'}
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block mb-1 font-medium">Name *</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div>
              <label className="block mb-1 font-medium">Contact Person</label>
              <input
                type="text"
                value={formData.contact_person}
                onChange={(e) => setFormData({ ...formData, contact_person: e.target.value })}
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div>
              <label className="block mb-1 font-medium">Contact Phone</label>
              <input
                type="text"
                value={formData.contact_phone}
                onChange={(e) => setFormData({ ...formData, contact_phone: e.target.value })}
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div>
              <label className="block mb-1 font-medium">Contact Email</label>
              <input
                type="email"
                value={formData.contact_email}
                onChange={(e) => setFormData({ ...formData, contact_email: e.target.value })}
                className="w-full px-3 py-2 border rounded"
              />
            </div>
            <div className="flex gap-2">
              <button
                type="submit"
                className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              >
                {editingClient ? 'Update' : 'Create'}
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
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact Person</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Locations</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {clients.map((client) => (
              <tr key={client.id}>
                <td className="px-6 py-4">{client.name}</td>
                <td className="px-6 py-4">{client.contact_person || '-'}</td>
                <td className="px-6 py-4">{client.contact_phone || '-'}</td>
                <td className="px-6 py-4">{client.contact_email || '-'}</td>
                <td className="px-6 py-4">{client.locations_count || 0}</td>
                <td className="px-6 py-4">
                  {can('client.update') && (
                    <button
                      onClick={() => handleEdit(client)}
                      className="text-blue-600 hover:underline mr-3"
                    >
                      Edit
                    </button>
                  )}
                  {can('client.delete') && (
                    <button
                      onClick={() => handleDelete(client.id)}
                      className="text-red-600 hover:underline"
                    >
                      Delete
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {clients.length === 0 && (
          <div className="p-8 text-center text-gray-500">No clients found</div>
        )}
      </div>
    </div>
  );
}
