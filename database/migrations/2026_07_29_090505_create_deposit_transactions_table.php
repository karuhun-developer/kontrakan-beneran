<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('amount');
            $table->string('reason')->nullable();
            $table->date('occurred_on');
            $table->nullableMorphs('source');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['deposit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
    }
};
