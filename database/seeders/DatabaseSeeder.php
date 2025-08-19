<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Insertar unidades primero
        $unidadUdId = DB::table('articulo_unidades')->insertGetId([
            'abreviatura' => 'UD',
            'nombre' => 'UNIDAD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadLitroId = DB::table('articulo_unidades')->insertGetId([
            'abreviatura' => 'L',
            'nombre' => 'LITRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadMlId = DB::table('articulo_unidades')->insertGetId([
            'abreviatura' => 'ML',
            'nombre' => 'MILILITRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadKgId = DB::table('articulo_unidades')->insertGetId([
            'abreviatura' => 'KG',
            'nombre' => 'KILOGRAMO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $unidadGId = DB::table('articulo_unidades')->insertGetId([
            'abreviatura' => 'G',
            'nombre' => 'GRAMO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar marcas
        $repsolId = DB::table('articulo_marcas')->insertGetId([
            'nombre' => 'REPSOL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $wurthId = DB::table('articulo_marcas')->insertGetId([
            'nombre' => 'WURTH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar categorías
        $aceiteId = DB::table('articulo_categorias')->insertGetId([
            'nombre' => 'ACEITE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cintilloId = DB::table('articulo_categorias')->insertGetId([
            'nombre' => 'CINTILLO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insertar el artículo (usando litros como unidad)
        $articuloId = DB::table('articulos')->insertGetId([
            'categoria_id' => $aceiteId,
            'marca_id' => $repsolId,
            'descripcion' => '10W40',
            'descripcion_interna' => 'TAPA ROJA',
            'fraccionable' => true,
            'contenido' => 4.00,
            'unidad_id' => $unidadLitroId,
            'stock' => 10.00,
            'costo' => 120.00,
            'precio' => 200.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('articulos')->insert([
            'categoria_id' => $cintilloId,
            'marca_id' => $wurthId,
            'descripcion' => '200x4.8 MM',
            'fraccionable' => false,
            'unidad_id' => $unidadUdId,
            'stock' => 100.00,
            'costo' => 0.10,
            'precio' => 0.20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Administrador',
            'email' => 'admin@guzcar.com',
            'password' => Hash::make('pass123++'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Daniel',
            'email' => 'daniel@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Jorge',
            'email' => 'jorge@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Jauregui',
            'email' => 'jauregui@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Jose Peña',
            'email' => 'jose@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Moises',
            'email' => 'moises@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'Yahir Alcantara',
            'email' => 'yahir@guzcar.com',
            'password' => Hash::make('123456789'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('herramientas')->insert([
            'nombre' => 'LLAVE MIXTA 8 CHROME B',
            'costo' => 50,
            'stock' => 10,
        ]);

        DB::table('herramientas')->insert([
            'nombre' => 'DADOS 1/2 STANLEY',
            'costo' => 50,
            'stock' => 10,
        ]);
    }
}
