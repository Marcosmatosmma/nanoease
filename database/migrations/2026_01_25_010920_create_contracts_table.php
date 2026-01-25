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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 500);
            $table->string('contract_type', 100)->nullable(); // prestação de serviço, aluguel, SaaS, etc
            $table->text('parties_involved')->nullable(); // JSON com partes envolvidas
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('auto_renewal')->nullable(); // null = desconhecido
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', ['ativo', 'vencido', 'encerrado'])->default('ativo');
            $table->text('ai_summary')->nullable(); // resumo gerado pela IA
            $table->text('notes')->nullable(); // observações manuais
            $table->timestamps();
            
            // Índices para performance
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'end_date']);
            $table->index(['team_id', 'contract_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
