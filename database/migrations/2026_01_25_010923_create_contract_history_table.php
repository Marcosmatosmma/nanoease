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
        Schema::create('contract_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 100); // upload, status_change, alert_fired, ai_interaction, manual_edit
            $table->text('description');
            $table->json('metadata')->nullable(); // dados adicionais do evento
            $table->timestamp('created_at')->useCurrent();
            
            // Índices para performance
            $table->index(['contract_id', 'team_id']);
            $table->index(['team_id', 'created_at']);
            $table->index(['team_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_history');
    }
};
