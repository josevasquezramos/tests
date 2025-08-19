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
        Schema::create('herramienta_entrada_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('herramienta_entrada_id');
            $table->unsignedBigInteger('herramienta_id');
            $table->unsignedInteger('cantidad');
            $table->decimal('costo')->default(0);

            $table->index('herramienta_entrada_id', 'idx_hed_he');
            $table->index('herramienta_id', 'idx_hed_h');

            $table->foreign('herramienta_entrada_id')
                ->references('id')->on('herramienta_entradas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('herramienta_id')
                ->references('id')->on('herramientas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE herramienta_entrada_detalles
            ADD CONSTRAINT chk_hed_cantidad_gt_zero CHECK (cantidad > 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE herramienta_entrada_detalles DROP CHECK chk_hed_cantidad_gt_zero");
        } catch (\Throwable $e) {
        }
        Schema::dropIfExists('herramienta_entrada_detalles');
    }
};
