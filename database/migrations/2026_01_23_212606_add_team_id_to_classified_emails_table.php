<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona team_id à tabela classified_emails
     * IMPORTANTE: Sistema SaaS multi-tenant - todo email classificado pertence a um team
     */
    public function up(): void
    {
        Schema::table('classified_emails', function (Blueprint $table) {
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
            UPDATE classified_emails ce
            JOIN users u ON ce.user_id = u.id
            SET ce.team_id = u.current_team_id
            WHERE ce.team_id IS NULL
        ');

        // Agora torna NOT NULL
        Schema::table('classified_emails', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });

        // Adiciona novos índices compostos
        Schema::table('classified_emails', function (Blueprint $table) {
            $table->index(['team_id', 'user_id', 'automation_id']);
            $table->index(['team_id', 'email_date', 'user_id']);
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::table('classified_emails', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'user_id', 'automation_id']);
            $table->dropIndex(['team_id', 'email_date', 'user_id']);
            $table->dropIndex(['team_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
