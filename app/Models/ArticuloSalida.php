<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticuloSalida extends Model
{
    use HasFactory;

    protected $table = 'articulo_salidas';

    protected $fillable = [
        'articulo_id',
        'cantidad',
        'unidad_id',
        'articulo_abierto_id',
        'precio'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function unidad()
    {
        return $this->belongsTo(ArticuloUnidad::class);
    }

    public function abierto()
    {
        return $this->belongsTo(ArticuloAbierto::class, 'articulo_abierto_id');
    }
}
