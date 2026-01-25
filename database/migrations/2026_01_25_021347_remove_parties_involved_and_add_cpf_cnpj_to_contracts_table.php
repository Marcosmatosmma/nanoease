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
            // Remover parties_involved
            $table->dropColumn('parties_involved');
            
            // Adicionar CPF/CNPJ
            $table->string('contractor_cpf_cnpj', 18)->nullable()->after('contractor')->comment('CPF ou CNPJ do contratante');
            $table->string('contracted_cpf_cnpj', 18)->nullable()->after('contracted')->comment('CPF ou CNPJ do contratado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Restaurar parties_involved
            $table->json('parties_involved')->nullable();
            
            // Remover CPF/CNPJ
            $table->dropColumn(['contractor_cpf_cnpj', 'contracted_cpf_cnpj']);
        });
    }
};
