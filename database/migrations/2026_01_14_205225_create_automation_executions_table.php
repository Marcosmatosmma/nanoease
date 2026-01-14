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
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->cascadeOnDelete();
            $table->string('email_id')->comment('Gmail message ID');
            $table->string('email_subject')->nullable();
            $table->string('email_from')->nullable();
            $table->enum('status', ['success', 'failed', 'skipped'])->default('skipped');
            $table->text('reasoning')->nullable()->comment('AI decision reasoning');
            $table->float('confidence')->nullable();
            $table->json('action_result')->nullable();
            $table->timestamps();
            
            $table->unique(['automation_id', 'email_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automation_executions');
    }
};
