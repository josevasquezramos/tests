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
            $table->enum('tipo_origen', ['MALETA', 'STOCK']);
            $table->unsignedBigInteger('maleta_detalle_id')->nullable();
            $table->unsignedBigInteger('herramienta_id');
            $table->unsignedInteger('cantidad')->default(1);
            $table->unsignedBigInteger('propietario_id')->nullable();
            $table->unsignedBigInteger('responsable_id');
            $table->enum('motivo', ['MERMA', 'PERDIDO']);
            $table->enum('prev_estado', ['OPERATIVO', 'MERMA', 'PERDIDO'])->nullable();
            $table->dateTime('prev_deleted_at')->nullable();
            $table->text('observacion')->nullable();

            // Índices
            $table->index('tipo_origen', 'idx_hi_tipo');
            $table->index('maleta_detalle_id', 'idx_hi_md');
            $table->index('herramienta_id', 'idx_hi_h');
            $table->index('propietario_id', 'idx_hi_prop');
            $table->index('responsable_id', 'idx_hi_resp');

            // Foreign Keys
            $table->foreign('maleta_detalle_id')
                ->references('id')->on('maleta_detalles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('herramienta_id')
                ->references('id')->on('herramientas')
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

        // Después de crear la tabla, agregar el CHECK constraint
        // Laravel no soporta CHECK constraints nativamente, 
        // así que lo agregamos con SQL crudo
        DB::statement('
    ALTER TABLE herramienta_incidencias 
    ADD CONSTRAINT chk_hi_coherencia CHECK (
        (tipo_origen = "MALETA" AND cantidad = 1) OR
        (tipo_origen = "STOCK" AND cantidad > 0)
    )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('herramienta_incidencias');
    }
};
