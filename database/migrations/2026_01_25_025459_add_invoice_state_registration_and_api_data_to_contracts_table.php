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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('invoice_state_registration')->nullable()->after('invoice_recipient_cnpj')->comment('Inscrição Estadual');
            $table->json('invoice_cnpj_api_data')->nullable()->after('invoice_service_code')->comment('Dados da API do CNPJ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['invoice_state_registration', 'invoice_cnpj_api_data']);
        });
    }
};
