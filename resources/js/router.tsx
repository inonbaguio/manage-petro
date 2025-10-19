import { createBrowserRouter, Outlet } from "react-router-dom";
import { AuthProvider } from "./contexts/AuthContext";
import { ProtectedRoute } from "./components/ProtectedRoute";
import { DashboardLayout } from "./layouts/DashboardLayout";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import ClientsPage from "./pages/ClientsPage";
import LocationsPage from "./pages/LocationsPage";
import TrucksPage from "./pages/TrucksPage";
import OrdersPage from "./pages/OrdersPage";
import ActivityLogPage from "./pages/ActivityLogPage";

const TenantLayout = () => (
  <AuthProvider>
    <Outlet />
  </AuthProvider>
);

export const router = createBrowserRouter([
  {
    path: "/:tenant",
    element: <TenantLayout />,
    children: [
      { path: "login", element: <Login /> },
      {
        path: "",
        element: (
          <ProtectedRoute>
            <DashboardLayout />
          </ProtectedRoute>
        ),
        children: [
          { path: "dashboard", element: <Dashboard /> },
          { path: "clients", element: <ClientsPage /> },
          { path: "locations", element: <LocationsPage /> },
          { path: "trucks", element: <TrucksPage /> },
          { path: "orders", element: <OrdersPage /> },
          { path: "activity-logs", element: <ActivityLogPage /> },
          { index: true, element: <Dashboard /> },
        ],
      },
    ],
  },
  { path: "*", element: <div className="p-8 text-center">404 - Not Found</div> },
]);
