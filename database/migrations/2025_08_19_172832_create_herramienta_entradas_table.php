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
        Schema::create('herramienta_entradas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo');
            $table->dateTime('fecha');
            $table->text('observacion')->nullable();
            $table->unsignedBigInteger('responsable_id');
            $table->string('evidencia_url')->nullable();

            $table->unique('codigo', 'uk_he_codigo');

            $table->foreign('responsable_id')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('herramienta_entradas');
    }
};
