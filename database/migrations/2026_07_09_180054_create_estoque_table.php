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
        Schema::create('estoque', function (Blueprint $table) {
            $table->id('codigoEstoque');
            $table->unsignedBigInteger('codigoFornecedor');
            $table->string('codigoProduto', 50)->unique();
            $table->string('descricaoProduto', 150);
            $table->string('categoriaProduto', 50);
            $table->string('unidadeMedidaProduto')->default('UN'); // Ex: UN, L, ML, KG,
            $table->decimal('quantidadeProduto', 10, 2)->default(0.00);
            $table->integer('estoqueMinimoProduto')->default(5);
            $table->decimal('valorProduto', 10, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo
            $table->foreign('codigoFornecedor')->references('codigoFornecedor')->on('fornecedores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoque');
    }
};
