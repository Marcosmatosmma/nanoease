<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de listas do board (colunas do Kanban)
     * Representam as fases/status das tarefas
     * Exemplos: A Fazer, Em Andamento, Concluído
     */
    public function up(): void
    {
        Schema::create('board_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable(); // Cor da lista (opcional)
            $table->integer('position')->default(0); // Ordem de exibição
            $table->boolean('is_default')->default(false); // Lista padrão
            $table->timestamps();

            $table->index(['board_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_lists');
    }
};
