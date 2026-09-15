<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('notification_deliveries')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('outcome', 30);
            $table->string('error_class')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            $table->unique(['delivery_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_attempts');
    }
};
