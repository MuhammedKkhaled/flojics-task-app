<?php

use App\Enums\EscalationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('status', 30)->default(EscalationStatus::Pending->value);
            $table->text('reason')->nullable();
            $table->timestamp('escalated_at');
            $table->timestamps();

            $table->unique(['ticket_id', 'level']);
            $table->index(['status', 'escalated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_escalations');
    }
};
