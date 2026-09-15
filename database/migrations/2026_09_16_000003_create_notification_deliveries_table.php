<?php

use App\Enums\DeliveryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('notifiable');
            $table->string('channel', 50);
            $table->string('recipient');
            $table->string('status', 30)->default(DeliveryStatus::Pending->value)->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(4);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('last_error_code', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['notifiable_type', 'notifiable_id', 'channel', 'recipient'],
                'notification_delivery_destination_unique',
            );
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE notification_deliveries '
                .'ADD CONSTRAINT notification_deliveries_attempts_check '
                .'CHECK (attempts <= max_attempts)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
