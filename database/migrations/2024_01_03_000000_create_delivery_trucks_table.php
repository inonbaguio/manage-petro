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
        Schema::create('delivery_trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('plate_no');
            $table->integer('tank_capacity_l'); // liters
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Composite unique: plate_no must be unique per tenant
            $table->unique(['plate_no', 'tenant_id']);
            // Composite unique index for foreign key references
            $table->unique(['id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_trucks');
    }
};
