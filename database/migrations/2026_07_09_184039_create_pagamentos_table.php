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
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id('codigoPagamento');
            $table->unsignedBigInteger('codigoOs');
            $table->string('formaPagamento'); // Dinheiro, Pix, Cartão de Crédito, Cartão de Débito, etc.
            $table->decimal('valorPagamento', 10, 2);
            $table->integer('parcelasPagamento')->default(1);
            $table->string('observacaoPagamento')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo para histórico de auditoria
            $table->foreign('codigoOs')->references('codigoOs')->on('os');
        });    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
