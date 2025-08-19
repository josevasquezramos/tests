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
        Schema::create('herramienta_incidencias', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha');
            $table->unsignedBigInteger('maleta_detalle_id');
            $table->unsignedBigInteger('propietario_id')->nullable();
            $table->unsignedBigInteger('responsable_id');
            $table->enum('motivo', ['MERMA', 'PERDIDO']);
            $table->enum('prev_estado', ['OPERATIVO', 'MERMA', 'PERDIDO'])->nullable();
            $table->dateTime('prev_deleted_at')->nullable();
            $table->text('observacion')->nullable();

            $table->index('maleta_detalle_id', 'idx_hi_md');
            $table->index('propietario_id', 'idx_hi_prop');
            $table->index('responsable_id', 'idx_hi_resp');

            $table->foreign('maleta_detalle_id')
                ->references('id')->on('maleta_detalles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('propietario_id')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

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
        Schema::dropIfExists('herramienta_incidencias');
    }
};
