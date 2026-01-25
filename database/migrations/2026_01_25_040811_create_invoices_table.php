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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            
            // Arquivos
            $table->string('pdf_path')->nullable()->comment('Caminho do PDF da NF');
            $table->string('xml_path')->nullable()->comment('Caminho do XML da NF');
            
            // Dados extraídos/preenchidos
            $table->date('invoice_date')->nullable()->comment('Data de emissão da NF');
            $table->date('due_date')->nullable()->comment('Data de vencimento');
            $table->decimal('amount', 15, 2)->nullable()->comment('Valor da NF');
            $table->string('invoice_number')->nullable()->comment('Número da NF');
            $table->text('description')->nullable()->comment('Descrição/Observação');
            
            // Dados brutos do XML para consulta
            $table->json('xml_data')->nullable()->comment('Dados completos extraídos do XML');
            
            $table->timestamps();
            
            // Índices
            $table->index('contract_id');
            $table->index('team_id');
            $table->index('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
