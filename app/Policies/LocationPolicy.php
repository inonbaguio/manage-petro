<?php

namespace App\Policies;

use App\Modules\Locations\Domain\Models\Location;
use App\Modules\Users\Domain\Models\User;

class LocationPolicy
{
    /**
     * Determine whether the user can view any locations.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view locations
        return in_array($user->role, ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']);
    }

    /**
     * Determine whether the user can view the location.
     */
    public function view(User $user, Location $location): bool
    {
        // ADMIN and DISPATCHER can view all locations
        if (in_array($user->role, ['ADMIN', 'DISPATCHER'])) {
            return true;
        }

        // CLIENT_REP can view locations for their client
        // TODO: Add client_id to users table to enable this check
        // if ($user->role === 'CLIENT_REP' && $location->client_id === $user->client_id) {
        //     return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can create locations.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }
}
