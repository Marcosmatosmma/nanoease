<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de integrações
     * IMPORTANTE: Sistema SaaS multi-tenant - toda integração pertence a um team
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete(); // Multi-tenancy
            $table->string('provider');
            $table->string('status')->default('disconnected');
            $table->json('metadata')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Constraint único: um usuário pode ter apenas 1 integração de cada provider por team
            $table->unique(['team_id', 'user_id', 'provider']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
