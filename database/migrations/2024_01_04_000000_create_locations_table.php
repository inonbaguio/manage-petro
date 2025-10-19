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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();

            // Composite FK: ensures location's client belongs to same tenant
            $table->foreign(['client_id', 'tenant_id'])
                  ->references(['id', 'tenant_id'])
                  ->on('clients')
                  ->restrictOnDelete();

            // Composite unique index for foreign key references
            $table->unique(['id', 'tenant_id']);
            $table->index('tenant_id');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
