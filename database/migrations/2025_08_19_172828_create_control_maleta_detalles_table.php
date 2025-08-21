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
        Schema::create('control_maleta_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('control_maleta_id');
            $table->unsignedBigInteger('maleta_detalle_id');
            $table->unsignedBigInteger('herramienta_id');
            $table->enum('estado', ['OPERATIVO', 'MERMA', 'PERDIDO'])->default('OPERATIVO');
            $table->text('observacion')->nullable();
            $table->enum('prev_estado', ['OPERATIVO', 'MERMA', 'PERDIDO'])->nullable();
            $table->dateTime('prev_deleted_at')->nullable();

            $table->unique(['control_maleta_id', 'maleta_detalle_id'], 'uk_cmd_control_detalle');
            $table->index('control_maleta_id', 'idx_cmd_control');
            $table->index('maleta_detalle_id', 'idx_cmd_md');
            $table->index('herramienta_id', 'idx_cmd_h');

            $table->foreign('control_maleta_id')
                ->references('id')->on('control_maletas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('maleta_detalle_id')
                ->references('id')->on('maleta_detalles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('herramienta_id')
                ->references('id')->on('herramientas')
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
        Schema::dropIfExists('control_maleta_detalles');
    }
};
