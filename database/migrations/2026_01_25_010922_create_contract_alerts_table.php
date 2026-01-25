<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('alert_type', 100); // before_expiration, on_expiration, after_expiration
            $table->integer('days_before')->nullable(); // ex: 15, 30, 60 (para before_expiration)
            $table->timestamp('triggered_at')->nullable(); // quando o alerta foi disparado
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete(); // tarefa criada
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices para performance
            $table->index(['contract_id', 'team_id']);
            $table->index(['team_id', 'triggered_at']);
            $table->index(['team_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_alerts');
    }
};
