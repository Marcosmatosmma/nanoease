<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de tarefas (cards do Kanban)
     * Representa uma atividade executável
     * Pode ser criada manualmente, por automação ou formulário externo
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_list_id')->constrained('board_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Criador
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Responsável
            
            // Dados principais da tarefa
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable(); // Data de vencimento
            $table->integer('position')->default(0); // Ordem dentro da lista
            
            // Origem da tarefa (obrigatório conforme spec)
            $table->enum('source', ['manual', 'automation', 'form', 'system'])->default('manual');
            $table->string('source_id')->nullable(); // ID da automação ou formulário
            $table->json('source_metadata')->nullable(); // Dados extras da origem
            
            // Controle de status
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();

            // Índices para performance
            $table->index(['team_id', 'board_list_id']);
            $table->index(['team_id', 'assigned_to']);
            $table->index(['team_id', 'due_date']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
