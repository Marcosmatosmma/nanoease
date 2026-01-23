<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela mass_email_sends
     * Armazena informações do envio em massa de emails
     */
    public function up(): void
    {
        Schema::create('mass_email_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('integration_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('automation_event_id')->nullable()->constrained()->onDelete('set null');
            
            // Informações básicas
            $table->string('name'); // Nome do envio
            $table->enum('send_type', ['now', 'scheduled'])->default('now');
            $table->timestamp('scheduled_at')->nullable(); // Quando agendar
            
            // Conteúdo do email
            $table->string('subject');
            $table->text('body');
            
            // Fonte de dados
            $table->enum('recipient_source', ['csv', 'sheets'])->default('csv');
            $table->string('email_column')->default('email'); // Coluna que contém emails
            $table->json('columns')->nullable(); // Todas as colunas disponíveis
            $table->json('variable_mapping')->nullable(); // Mapeamento de variáveis
            
            // Validação e estatísticas
            $table->integer('total_recipients')->default(0);
            $table->integer('valid_recipients')->default(0);
            $table->integer('invalid_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            
            // Status do envio
            $table->enum('status', ['draft', 'scheduled', 'processing', 'completed', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Metadados adicionais
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_email_sends');
    }
};
