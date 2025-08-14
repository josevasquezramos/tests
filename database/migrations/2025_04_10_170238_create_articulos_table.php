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
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('articulo_categorias');
            $table->foreignId('marca_id')->nullable()->constrained('articulo_marcas');
            $table->string('descripcion', 255);
            $table->string('descripcion_interna', 255)->nullable();
            $table->boolean('fraccionable')->default(false);
            $table->decimal('contenido', 10, 2)->nullable();
            $table->foreignId('unidad_id')->default(1);
            $table->foreign('unidad_id')->references('id')->on('articulo_unidades');
            $table->decimal('stock', 10, 2)->default(0.00);
            $table->decimal('costo', 10, 2)->default(0.00);
            $table->decimal('precio', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
