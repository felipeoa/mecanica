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
        Schema::create('os', function (Blueprint $table) {
            $table->id('codigoOs');
            $table->unsignedBigInteger('codigoVeiculo');
            $table->enum('statusOs', ['Orçamento', 'Aberta', 'Aprovada', 'Em andamento', 'Finalizada'])->default('Orçamento');
            $table->decimal('totalOs', 10, 2)->default(0.00);
            $table->text('observacoesOs')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo
            $table->foreign('codigoVeiculo')->references('codigoVeiculo')->on('veiculos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('os');
    }
};
