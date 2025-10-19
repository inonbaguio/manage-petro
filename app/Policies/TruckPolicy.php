<?php

namespace App\Policies;

use App\Modules\Trucks\Domain\Models\DeliveryTruck;
use App\Models\User;

class TruckPolicy
{
    /**
     * Determine whether the user can view any trucks.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view trucks
        return in_array($user->role, ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']);
    }

    /**
     * Determine whether the user can view the truck.
     */
    public function view(User $user, DeliveryTruck $truck): bool
    {
        // All authenticated users can view individual trucks
        return in_array($user->role, ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']);
    }

    /**
     * Determine whether the user can create trucks.
     */
    public function create(User $user): bool
    {
        // Only ADMIN can create trucks
        return $user->role === 'ADMIN';
    }

    /**
     * Determine whether the user can update the truck.
     */
    public function update(User $user, DeliveryTruck $truck): bool
    {
        // ADMIN and DISPATCHER can update trucks
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can delete the truck.
     */
    public function delete(User $user, DeliveryTruck $truck): bool
    {
        // Only ADMIN can delete trucks
        return $user->role === 'ADMIN';
    }
}
