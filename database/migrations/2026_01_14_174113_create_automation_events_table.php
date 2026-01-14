<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_events', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // email_received, webhook_received, task_created
            $table->string('title'); // "E-mail recebido"
            $table->text('description'); // "Captura cada mensagem que chegar na caixa"
            $table->string('icon')->nullable(); // lucide:mail
            $table->string('category')->nullable(); // email, webhook, crm, task
            $table->integer('position')->default(0); // Ordem de exibição
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable(); // Dados extras (required_integrations, etc)
            $table->timestamps();
            
            $table->index('category');
            $table->index(['active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_events');
    }
};
