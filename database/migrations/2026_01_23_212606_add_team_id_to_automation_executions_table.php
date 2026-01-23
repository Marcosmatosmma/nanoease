<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona team_id à tabela automation_executions
     * IMPORTANTE: Sistema SaaS multi-tenant - toda execução pertence a um team
     */
    public function up(): void
    {
        Schema::table('automation_executions', function (Blueprint $table) {
            // Adiciona team_id NULLABLE primeiro (para popular depois)
            $table->foreignId('team_id')
                ->after('automation_id')
                ->nullable()
                ->constrained('teams')
                ->cascadeOnDelete();
            
            // Índice para buscar por team
            $table->index('team_id');
        });

        // Popula team_id com base no team_id da automation
        DB::statement('
            UPDATE automation_executions ae
            JOIN automations a ON ae.automation_id = a.id
            SET ae.team_id = a.team_id
            WHERE ae.team_id IS NULL
        ');

        // Agora torna NOT NULL
        Schema::table('automation_executions', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::table('automation_executions', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
