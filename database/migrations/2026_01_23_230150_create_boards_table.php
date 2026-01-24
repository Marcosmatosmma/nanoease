<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de boards (quadros Kanban)
     * Um board contém listas e tarefas organizadas
     * Multi-tenancy: cada board pertence a um team
     */
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#3b82f6'); // Cor do board
            $table->integer('position')->default(0); // Ordem de exibição
            $table->boolean('is_default')->default(false); // Board padrão
            $table->timestamps();

            $table->index(['team_id', 'user_id']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
