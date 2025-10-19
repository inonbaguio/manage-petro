<?php

namespace App\Policies;

use App\Modules\Clients\Domain\Models\Client;
use App\Modules\Users\Domain\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view clients
        return in_array($user->role, ['ADMIN', 'DISPATCHER', 'DRIVER', 'CLIENT_REP']);
    }

    /**
     * Determine whether the user can view the client.
     */
    public function view(User $user, Client $client): bool
    {
        // ADMIN and DISPATCHER can view all clients
        if (in_array($user->role, ['ADMIN', 'DISPATCHER'])) {
            return true;
        }

        // CLIENT_REP can view their own client
        // TODO: Add client_id to users table to enable this check
        // if ($user->role === 'CLIENT_REP' && $client->id === $user->client_id) {
        //     return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can create clients.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can update the client.
     */
    public function update(User $user, Client $client): bool
    {
        return in_array($user->role, ['ADMIN', 'DISPATCHER']);
    }

    /**
     * Determine whether the user can delete the client.
     */
    public function delete(User $user, Client $client): bool
    {
        // Only ADMIN can delete clients
        return $user->role === 'ADMIN';
    }
}
