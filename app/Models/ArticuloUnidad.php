<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticuloUnidad extends Model
{
    use HasFactory;

    protected $table = 'articulo_unidades';

    protected $fillable = [
        'abreviatura',
        'nombre'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'unidad_id');
    }

    public function salidas()
    {
        return $this->hasMany(ArticuloSalida::class, 'unidad_id');
    }
}
