<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();

            // Client & Location (with composite FKs)
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('location_id');

            // Truck (nullable, assigned during scheduling)
            $table->unsignedBigInteger('truck_id')->nullable();

            // Users (created_by, driver_id with composite FKs)
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('driver_id')->nullable();

            // Order details
            $table->integer('fuel_liters');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'SCHEDULED', 'EN_ROUTE', 'DELIVERED', 'CANCELLED'])
                  ->default('DRAFT');

            // Time windows
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();

            // Delivery info
            $table->integer('delivered_liters')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Composite FKs ensuring all relationships are within same tenant

            // Client FK
            $table->foreign(['client_id', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('clients')
                  ->restrictOnDelete();

            // Location FK
            $table->foreign(['location_id', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('locations')
                  ->restrictOnDelete();

            // Truck FK (nullable)
            $table->foreign(['truck_id', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('delivery_trucks')
                  ->restrictOnDelete();

            // Created By FK
            $table->foreign(['created_by', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('users')
                  ->restrictOnDelete();

            // Driver FK (nullable)
            $table->foreign(['driver_id', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('users')
                  ->restrictOnDelete();

            // Indexes
            $table->index('tenant_id');
            $table->index('client_id');
            $table->index('location_id');
            $table->index('truck_id');
            $table->index('created_by');
            $table->index('driver_id');
            $table->index('status');
            $table->index(['window_start', 'window_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
