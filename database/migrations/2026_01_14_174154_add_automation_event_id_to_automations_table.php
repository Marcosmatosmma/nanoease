<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->foreignId('automation_event_id')->nullable()->after('integration_id')->constrained('automation_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropForeign(['automation_event_id']);
            $table->dropColumn('automation_event_id');
        });
    }
};
