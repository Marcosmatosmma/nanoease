<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->foreignId('trigger_type_id')->nullable()->after('event')->constrained('automation_trigger_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropForeign(['trigger_type_id']);
            $table->dropColumn('trigger_type_id');
        });
    }
};
