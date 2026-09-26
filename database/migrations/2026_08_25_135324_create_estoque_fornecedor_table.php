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
        Schema::create('estoque_fornecedor', function (Blueprint $table) {
            $table->id('codigoEstoqueFornecedor');
            $table->unsignedBigInteger('codigoEstoque');
            $table->unsignedBigInteger('codigoFornecedor');
            $table->foreign('codigoEstoque')->references('codigoEstoque')->on('estoques')->onDelete('cascade');
            $table->foreign('codigoFornecedor')->references('codigoFornecedor')->on('fornecedores')->onDelete('cascade');
            $table->softDeletes(); // Soft Delete ativo para histórico de auditoria
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoque_fornecedor');
    }
};
