<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cria a tabela mass_email_recipients
     * Armazena status individual de cada destinatário
     */
    public function up(): void
    {
        Schema::create('mass_email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mass_email_send_id')->constrained()->onDelete('cascade');
            
            // Dados do destinatário
            $table->string('email');
            $table->json('data')->nullable(); // Dados completos da linha (nome, empresa, etc)
            
            // Status do envio
            $table->enum('status', ['pending', 'sent', 'failed', 'ignored'])->default('pending');
            $table->text('error_message')->nullable(); // Mensagem de erro se falhou
            $table->string('ignore_reason')->nullable(); // Motivo de ignorar (email inválido, duplicado, etc)
            
            // Rastreamento
            $table->timestamp('sent_at')->nullable();
            $table->string('message_id')->nullable(); // ID da mensagem Gmail
            
            // Metadados
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['mass_email_send_id', 'status']);
            $table->index('email');
        });
    }

    /**
     * Reverte a migration
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_email_recipients');
    }
};
