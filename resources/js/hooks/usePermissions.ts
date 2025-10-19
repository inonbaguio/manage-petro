import { useAuth } from '../contexts/AuthContext';

type Permission =
  | 'order.create'
  | 'order.view'
  | 'order.update'
  | 'order.delete'
  | 'order.submit'
  | 'order.schedule'
  | 'order.dispatch'
  | 'order.deliver'
  | 'order.cancel'
  | 'client.create'
  | 'client.view'
  | 'client.update'
  | 'client.delete'
  | 'location.create'
  | 'location.view'
  | 'location.update'
  | 'location.delete'
  | 'truck.create'
  | 'truck.view'
  | 'truck.update'
  | 'truck.delete'
  | 'activity_log.view';

type Role = 'ADMIN' | 'DISPATCHER' | 'DRIVER' | 'CLIENT_REP';

const permissionMatrix: Record<Permission, Role[]> = {
  // Order permissions
  'order.create': ['ADMIN', 'DISPATCHER'],
  'order.view': ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP'],
  'order.update': ['ADMIN', 'DISPATCHER'],
  'order.delete': ['ADMIN', 'DISPATCHER'],
  'order.submit': ['ADMIN', 'DISPATCHER'],
  'order.schedule': ['ADMIN', 'DISPATCHER'],
  'order.dispatch': ['ADMIN', 'DISPATCHER'],
  'order.deliver': ['ADMIN', 'DRIVER'],
  'order.cancel': ['ADMIN', 'DISPATCHER'],

  // Client permissions
  'client.create': ['ADMIN', 'DISPATCHER'],
  'client.view': ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP'],
  'client.update': ['ADMIN', 'DISPATCHER'],
  'client.delete': ['ADMIN'],

  // Location permissions
  'location.create': ['ADMIN', 'DISPATCHER'],
  'location.view': ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP'],
  'location.update': ['ADMIN', 'DISPATCHER'],
  'location.delete': ['ADMIN', 'DISPATCHER'],

  // Truck permissions
  'truck.create': ['ADMIN'],
  'truck.view': ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP'],
  'truck.update': ['ADMIN', 'DISPATCHER'],
  'truck.delete': ['ADMIN'],

  // Activity log permissions
  'activity_log.view': ['ADMIN'],
};

export function usePermissions() {
  const { user } = useAuth();

  /**
   * Check if the current user has a specific permission
   */
  const can = (permission: Permission): boolean => {
    if (!user || !user.role) {
      return false;
    }

    const allowedRoles = permissionMatrix[permission];
    return allowedRoles ? allowedRoles.includes(user.role as Role) : false;
  };

  /**
   * Check if the current user has any of the specified permissions
   */
  const canAny = (permissions: Permission[]): boolean => {
    return permissions.some((permission) => can(permission));
  };

  /**
   * Check if the current user has all of the specified permissions
   */
  const canAll = (permissions: Permission[]): boolean => {
    return permissions.every((permission) => can(permission));
  };

  // Role checkers
  const isAdmin = user?.role === 'ADMIN';
  const isDispatcher = user?.role === 'DISPATCHER';
  const isDriver = user?.role === 'DRIVER';
  const isClientRep = user?.role === 'CLIENT_REP';

  return {
    can,
    canAny,
    canAll,
    isAdmin,
    isDispatcher,
    isDriver,
    isClientRep,
    role: user?.role,
  };
}
