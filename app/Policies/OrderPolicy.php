<?php

namespace App\Policies;

use App\Modules\Orders\Domain\Models\Order;
use App\Modules\Users\Domain\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view orders
        return in_array($user->role, ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']);
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // ADMIN and DISPATCHER can view all orders
        if (in_array($user->role, ['ADMIN', 'DISPATCHER'])) {
            return true;
        }

        // DRIVER can view orders assigned to them
        if ($user->role === 'DRIVER' && $order->driver_id === $user->id) {
            return true;
        }

        // CLIENT_REP can view orders for their client
        // TODO: Add client_id to users table to enable this check
        // if ($user->role === 'CLIENT_REP' && $order->client_id === $user->client_id) {
        //     return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Only ADMIN and DISPATCHER can update orders
        if (in_array($user->role, ['ADMIN', 'DISPATCHER'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only ADMIN and DISPATCHER can delete orders
        // Orders must be in DRAFT status to be deleted (enforced in service layer)
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can submit the order.
     */
    public function submit(User $user, Order $order): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can schedule the order.
     */
    public function schedule(User $user, Order $order): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can dispatch the order.
     */
    public function dispatch(User $user, Order $order): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can deliver the order.
     */
    public function deliver(User $user, Order $order): bool
    {
        // ADMIN can deliver any order
        if ($user->role === 'ADMIN') {
            return true;
        }

        // DRIVER can only deliver orders assigned to them
        if ($user->role === 'DRIVER' && $order->driver_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // ADMIN and DISPATCHER can cancel orders
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }
}
