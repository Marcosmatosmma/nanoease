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
        Schema::create('classified_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();
            
            $table->string('gmail_id')->nullable();
            $table->string('email_message_id', 500)->nullable();
            $table->string('email_from')->nullable();
            $table->text('email_subject')->nullable();
            $table->timestamp('email_date')->nullable();
            $table->string('gmail_label')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'automation_id']);
            $table->index('email_message_id');
            $table->index(['email_date', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classified_emails');
    }
};
