<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maleta_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maleta_id');
            $table->unsignedBigInteger('herramienta_id');
            $table->enum('ultimo_estado', ['OPERATIVO', 'MERMA', 'PERDIDO'])->nullable();
            $table->string('evidencia_url')->nullable();

            $table->index('maleta_id', 'idx_md_maleta');
            $table->index('herramienta_id', 'idx_md_herramienta');

            $table->foreign('maleta_id')
                ->references('id')->on('maletas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('herramienta_id')
                ->references('id')->on('herramientas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maleta_detalles');
    }
};
