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
        Schema::create('herramientas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('costo')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('asignadas')->default(0);
            $table->unsignedInteger('mermas')->default(0);
            $table->unsignedInteger('perdidas')->default(0);
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE herramientas
            ADD CONSTRAINT chk_herramientas_nonneg
            CHECK (stock >= 0 AND asignadas >= 0 AND mermas >= 0 AND perdidas >= 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE herramientas DROP CHECK chk_herramientas_nonneg");
        } catch (\Throwable $e) {
        }
        Schema::dropIfExists('herramientas');
    }
};
