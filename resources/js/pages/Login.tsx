import React, { useState } from "react";
import { useTenant } from "../lib/useTenant";
import { useAuth } from "../contexts/AuthContext";

export default function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const tenant = useTenant();
  const { login } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      await login(email, password);
    } catch (err: any) {
      setError(err.message || "Login failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto p-4">
      <div className="max-w-md mx-auto mt-8">
        <h1 className="text-2xl font-bold mb-2">Manage Petro</h1>
        <p className="text-gray-600 mb-6">Tenant: {tenant}</p>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="email" className="block mb-1">
              Email
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-3 py-2 border rounded"
            />
          </div>

          <div>
            <label htmlFor="password" className="block mb-1">
              Password
            </label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="w-full px-3 py-2 border rounded"
            />
          </div>

          {error && (
            <div className="p-3 bg-red-100 text-red-700 rounded">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {loading ? "Logging in..." : "Login"}
          </button>
        </form>

        <div className="mt-6 p-4 bg-gray-50 rounded">
          <p className="font-medium mb-2">Test Credentials:</p>
          <div className="text-sm space-y-1">
            <p>Admin: admin@{tenant}.test / password</p>
            <p>Dispatcher: dispatcher@{tenant}.test / password</p>
            <p>Driver: driver@{tenant}.test / password</p>
            <p>Client Rep: clientrep@{tenant}.test / password</p>
          </div>
        </div>
      </div>
    </div>
  );
}
