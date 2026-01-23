<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona team_id à tabela mass_email_sends
     * IMPORTANTE: Sistema SaaS multi-tenant - todo envio em massa pertence a um team
     */
    public function up(): void
    {
        Schema::table('mass_email_sends', function (Blueprint $table) {
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
            UPDATE mass_email_sends m
            JOIN users u ON m.user_id = u.id
            SET m.team_id = u.current_team_id
            WHERE m.team_id IS NULL
        ');

        // Agora torna NOT NULL
        Schema::table('mass_email_sends', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable(false)->change();
        });

        // Adiciona novo índice composto
        Schema::table('mass_email_sends', function (Blueprint $table) {
            $table->index(['team_id', 'user_id', 'status']);
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::table('mass_email_sends', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'user_id', 'status']);
            $table->dropIndex(['team_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
