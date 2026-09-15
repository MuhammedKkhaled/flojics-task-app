<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('status');
            $table->unsignedTinyInteger('escalation_level')->nullable()->after('escalated_at');

            $table->index('escalated_at');
            $table->index(['status', 'escalation_level']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['escalated_at']);
            $table->dropIndex(['status', 'escalation_level']);
            $table->dropColumn(['escalated_at', 'escalation_level']);
        });
    }
};
