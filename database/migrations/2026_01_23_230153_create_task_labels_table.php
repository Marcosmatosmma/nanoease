<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria tabela de etiquetas (labels) das tarefas
     * Labels ajudam a categorizar e filtrar tarefas
     * Exemplos: urgente, importante, bug, feature
     */
    public function up(): void
    {
        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name'); // Nome da etiqueta
            $table->string('color')->default('#6b7280'); // Cor em hex
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'name']); // Nome único por team
            $table->index('team_id');
        });

        // Tabela pivot: task_label (relacionamento many-to-many)
        Schema::create('task_task_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained('task_labels')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'task_label_id']);
            $table->index('task_id');
            $table->index('task_label_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_task_label');
        Schema::dropIfExists('task_labels');
    }
};
