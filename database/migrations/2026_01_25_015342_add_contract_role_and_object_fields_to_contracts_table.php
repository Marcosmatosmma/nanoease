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
            $table->enum('my_role', ['contratante', 'contratado'])->nullable()->after('contract_type');
            $table->text('contract_object')->nullable()->after('my_role')->comment('Objeto/escopo do contrato');
            $table->string('contract_number')->nullable()->after('contract_object')->comment('Número do contrato');
            $table->string('contractor')->nullable()->after('contract_number')->comment('Quem está contratando');
            $table->string('contracted')->nullable()->after('contractor')->comment('Quem foi contratado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['my_role', 'contract_object', 'contract_number', 'contractor', 'contracted']);
        });
    }
};
