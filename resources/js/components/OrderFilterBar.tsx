import React, { useState } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';

interface OrderFilters {
  date_from?: string;
  date_to?: string;
  status?: string[];
  client_id?: string;
  location_id?: string;
  driver_id?: string;
  truck_id?: string;
}

interface OrderFilterBarProps {
  onFilterChange: (filters: OrderFilters) => void;
  clients: Array<{ id: number; name: string }>;
  locations: Array<{ id: number; address: string; client_id: number }>;
  trucks: Array<{ id: number; plate_no: string }>;
  users: Array<{ id: number; name: string; role: string }>;
}

const ORDER_STATUSES = ['DRAFT', 'SUBMITTED', 'SCHEDULED', 'EN_ROUTE', 'DELIVERED', 'CANCELLED'];

export default function OrderFilterBar({ onFilterChange, clients, locations, trucks, users }: OrderFilterBarProps) {
  const [dateFrom, setDateFrom] = useState<Date | null>(null);
  const [dateTo, setDateTo] = useState<Date | null>(null);
  const [selectedStatuses, setSelectedStatuses] = useState<string[]>([]);
  const [clientId, setClientId] = useState<string>('');
  const [locationId, setLocationId] = useState<string>('');
  const [driverId, setDriverId] = useState<string>('');
  const [truckId, setTruckId] = useState<string>('');

  const drivers = users.filter(u => u.role === 'DRIVER');
  const filteredLocations = clientId
    ? locations.filter(l => l.client_id === parseInt(clientId))
    : locations;

  const handleStatusToggle = (status: string) => {
    setSelectedStatuses(prev =>
      prev.includes(status)
        ? prev.filter(s => s !== status)
        : [...prev, status]
    );
  };

  const applyFilters = () => {
    const filters: OrderFilters = {};

    if (dateFrom) filters.date_from = dateFrom.toISOString().split('T')[0];
    if (dateTo) filters.date_to = dateTo.toISOString().split('T')[0];
    if (selectedStatuses.length > 0) filters.status = selectedStatuses;
    if (clientId) filters.client_id = clientId;
    if (locationId) filters.location_id = locationId;
    if (driverId) filters.driver_id = driverId;
    if (truckId) filters.truck_id = truckId;

    onFilterChange(filters);
  };

  const clearFilters = () => {
    setDateFrom(null);
    setDateTo(null);
    setSelectedStatuses([]);
    setClientId('');
    setLocationId('');
    setDriverId('');
    setTruckId('');
    onFilterChange({});
  };

  return (
    <div className="bg-white p-4 rounded-lg shadow mb-6">
      <h3 className="font-semibold mb-4">Filter Orders</h3>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Date Range */}
        <div>
          <label className="block text-sm font-medium mb-1">Date From</label>
          <DatePicker
            selected={dateFrom}
            onChange={setDateFrom}
            dateFormat="yyyy-MM-dd"
            className="w-full px-3 py-2 border rounded"
            placeholderText="Select start date"
          />
        </div>

        <div>
          <label className="block text-sm font-medium mb-1">Date To</label>
          <DatePicker
            selected={dateTo}
            onChange={setDateTo}
            dateFormat="yyyy-MM-dd"
            className="w-full px-3 py-2 border rounded"
            placeholderText="Select end date"
          />
        </div>

        {/* Client Filter */}
        <div>
          <label className="block text-sm font-medium mb-1">Client</label>
          <select
            value={clientId}
            onChange={(e) => {
              setClientId(e.target.value);
              setLocationId(''); // Reset location when client changes
            }}
            className="w-full px-3 py-2 border rounded"
          >
            <option value="">All Clients</option>
            {clients.map(client => (
              <option key={client.id} value={client.id}>{client.name}</option>
            ))}
          </select>
        </div>

        {/* Location Filter */}
        <div>
          <label className="block text-sm font-medium mb-1">Location</label>
          <select
            value={locationId}
            onChange={(e) => setLocationId(e.target.value)}
            className="w-full px-3 py-2 border rounded"
            disabled={!clientId && locations.length === 0}
          >
            <option value="">All Locations</option>
            {filteredLocations.map(location => (
              <option key={location.id} value={location.id}>{location.address}</option>
            ))}
          </select>
        </div>

        {/* Truck Filter */}
        <div>
          <label className="block text-sm font-medium mb-1">Truck</label>
          <select
            value={truckId}
            onChange={(e) => setTruckId(e.target.value)}
            className="w-full px-3 py-2 border rounded"
          >
            <option value="">All Trucks</option>
            {trucks.map(truck => (
              <option key={truck.id} value={truck.id}>{truck.plate_no}</option>
            ))}
          </select>
        </div>

        {/* Driver Filter */}
        <div>
          <label className="block text-sm font-medium mb-1">Driver</label>
          <select
            value={driverId}
            onChange={(e) => setDriverId(e.target.value)}
            className="w-full px-3 py-2 border rounded"
          >
            <option value="">All Drivers</option>
            {drivers.map(driver => (
              <option key={driver.id} value={driver.id}>{driver.name}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Status Multi-Select */}
      <div className="mt-4">
        <label className="block text-sm font-medium mb-2">Status</label>
        <div className="flex flex-wrap gap-2">
          {ORDER_STATUSES.map(status => (
            <button
              key={status}
              onClick={() => handleStatusToggle(status)}
              className={`px-3 py-1 rounded text-sm font-medium transition ${
                selectedStatuses.includes(status)
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {status}
            </button>
          ))}
        </div>
      </div>

      {/* Action Buttons */}
      <div className="mt-4 flex gap-2">
        <button
          onClick={applyFilters}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Apply Filters
        </button>
        <button
          onClick={clearFilters}
          className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
        >
          Clear All
        </button>
      </div>
    </div>
  );
}
