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
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id('codigoVeiculo');
            $table->unsignedBigInteger('codigoCliente');
            $table->string('marcaVeiculo', 50);
            $table->string('modeloVeiculo', 50);
            $table->integer('anoVeiculo');
            $table->string('corVeiculo', 30);
            $table->string('placaVeiculo', 10)->unique();
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo
            $table->foreign('codigoCliente')->references('codigoCliente')->on('clientes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
