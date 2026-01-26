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
        Schema::table('contract_alerts', function (Blueprint $table) {
            $table->boolean('send_email')->default(false)->after('is_active');
            $table->string('email_to')->nullable()->after('send_email');
            $table->text('email_cc')->nullable()->after('email_to'); // JSON array de e-mails
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_alerts', function (Blueprint $table) {
            $table->dropColumn(['send_email', 'email_to', 'email_cc']);
        });
    }
};
