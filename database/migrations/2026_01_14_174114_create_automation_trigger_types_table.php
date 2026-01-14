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
        Schema::create('automation_trigger_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_event_id')->constrained('automation_events')->cascadeOnDelete();
            $table->string('key')->unique(); // sender_exact, sender_domain, etc
            $table->string('title'); // "Remetente específico"
            $table->text('description'); // "Match exato do e-mail"
            $table->string('icon')->nullable(); // lucide:at-sign
            $table->text('placeholder')->nullable(); // "mitsloan@getsmarter.com"
            $table->boolean('uses_ai')->default(false); // true para content_semantic
            $table->json('validation_rules')->nullable(); // Regras de validação específicas
            $table->integer('position')->default(0); // Ordem de exibição
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index(['automation_event_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_trigger_types');
    }
};
