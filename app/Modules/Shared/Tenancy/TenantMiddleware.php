<?php

namespace App\Modules\Shared\Tenancy;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');

        if (!$slug) {
            abort(400, 'Tenant slug is required');
        }

        $tenant = Tenant::where('slug', $slug)->first();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // Bind tenant to container for global access
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
