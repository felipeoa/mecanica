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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id('codigoEmpresa');
            $table->string('razaoSocialEmpresa');
            $table->string('nomeFantasiaEmpresa')->nullable();
            $table->string('cnpjEmpresa', 20)->nullable();
            $table->string('telefoneEmpresa', 20)->nullable();
            $table->string('emailEmpresa')->nullable();
            $table->string('enderecoEmpresa')->nullable();
            $table->string('cidadeEmpresa')->nullable();
            $table->string('ufEmpresa', 2)->nullable();
            $table->string('cepEmpresa', 10)->nullable();
            $table->string('logoPathEmpresa')->nullable(); // Caminho do arquivo da logo
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo para histórico de auditoria
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
