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
        Schema::create('clientes', function (Blueprint $table) 
        {
            $table->id('codigoCliente');
            $table->string('nomeCliente', 150);
            $table->string('cpfCliente', 14)->unique();
            $table->date('dataNascimentoCliente');
            $table->string('telefoneCliente', 20);
            $table->string('enderecoCliente', 150);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
