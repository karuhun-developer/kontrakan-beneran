<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('days_before');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['invoice_id', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
    }
};
