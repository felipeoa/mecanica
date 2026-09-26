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
        Schema::create('itens_os', function (Blueprint $table) {
            $table->id('codigoItemOs');
            $table->unsignedBigInteger('codigoOS');
            $table->string('codigoItem', 50); 
            $table->enum('tipoItem', ['produto', 'servico']);
            $table->integer('quantidadeItem')->default(1);
            $table->decimal('valorItem', 10, 2); 
            $table->timestamps();
            $table->softDeletes(); // Soft Delete ativo
            $table->foreign('codigoOS')->references('codigoOS')->on('os');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_os');
    }
};
