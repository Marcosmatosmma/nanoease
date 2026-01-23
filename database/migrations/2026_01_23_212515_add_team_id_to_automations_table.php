<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona team_id à tabela automations
     * IMPORTANTE: Sistema SaaS multi-tenant - toda automação pertence a um team
     */
    public function up(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            // Adiciona team_id NULLABLE primeiro (para popular depois)
            $table->foreignId('team_id')
                ->after('user_id')
                ->nullable()
                ->constrained('teams')
                ->cascadeOnDelete();
            
            // Índice para buscar por team
            $table->index('team_id');
        });

        // Popula team_id com base no current_team_id do user
        DB::statement('
            UPDATE automations a
            JOIN users u ON a.user_id = u.id
            SET a.team_id = u.current_team_id
            WHERE a.team_id IS NULL
        ');

        // Agora torna NOT NULL
        Schema::table('automations', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropIndex(['team_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
