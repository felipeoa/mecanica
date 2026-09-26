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
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id('codigoFornecedor');
            $table->string('nomeFantasiaFornecedor', 150);
            $table->string('razaoSocialFornecedor', 150)->nullable();
            $table->string('cnpjFornecedor', 18)->unique()->nullable();
            $table->string('telefoneFornecedor', 20);
            $table->string('emailFornecedor', 100)->nullable();
            $table->string('vendedorFornecedor', 150);
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
