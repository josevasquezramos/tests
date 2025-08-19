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
        Schema::create('control_maletas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maleta_id');
            $table->dateTime('fecha');
            $table->unsignedBigInteger('responsable_id');
            $table->unsignedBigInteger('propietario_id')->nullable();

            $table->index('maleta_id', 'idx_cm_maleta');
            $table->index('responsable_id', 'idx_cm_responsable');
            $table->index('propietario_id', 'idx_cm_propietario');

            $table->foreign('maleta_id')
                ->references('id')->on('maletas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('responsable_id')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('propietario_id')
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
        Schema::dropIfExists('control_maletas');
    }
};
