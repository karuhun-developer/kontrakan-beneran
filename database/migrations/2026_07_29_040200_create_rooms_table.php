<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('name')->nullable();
            $table->unsignedInteger('floor')->default(1);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('monthly_price')->nullable();
            $table->string('status')->default('active');
            $table->string('room_status')->default('available');
            $table->timestamps();

            $table->unique(['branch_id', 'number']);
            $table->index(['branch_id', 'room_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
