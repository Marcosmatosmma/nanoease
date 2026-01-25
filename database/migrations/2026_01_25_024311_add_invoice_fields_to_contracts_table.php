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
            // Campos de Nota Fiscal (quando sou contratado)
            $table->string('invoice_contact_email')->nullable()->after('payment_terms')->comment('Email para envio da NF');
            $table->string('invoice_contact_link')->nullable()->after('invoice_contact_email')->comment('Link/Portal para envio da NF');
            $table->string('invoice_system')->nullable()->after('invoice_contact_link')->comment('Sistema de cadastro da NF');
            $table->text('invoice_description')->nullable()->after('invoice_system')->comment('Descrição do serviço para a NF');
            $table->text('invoice_internal_notes')->nullable()->after('invoice_description')->comment('Observações internas sobre NF');
            $table->string('invoice_recipient_name')->nullable()->after('invoice_internal_notes')->comment('Nome do destinatário da NF');
            $table->string('invoice_recipient_cnpj')->nullable()->after('invoice_recipient_name')->comment('CNPJ do destinatário');
            $table->text('invoice_recipient_address')->nullable()->after('invoice_recipient_cnpj')->comment('Endereço do destinatário');
            $table->string('invoice_service_code')->nullable()->after('invoice_recipient_address')->comment('Código do serviço (ISS)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_contact_email',
                'invoice_contact_link',
                'invoice_system',
                'invoice_description',
                'invoice_internal_notes',
                'invoice_recipient_name',
                'invoice_recipient_cnpj',
                'invoice_recipient_address',
                'invoice_service_code',
            ]);
        });
    }
};
