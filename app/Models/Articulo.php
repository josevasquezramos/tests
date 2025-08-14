<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    use HasFactory;

    protected $table = 'articulos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'descripcion',
        'descripcion_interna',
        'fraccionable',
        'contenido',
        'unidad_id',
        'stock',
        'costo',
        'precio'
    ];

    protected $casts = [
        'fraccionable' => 'boolean',
        'contenido' => 'decimal:2',
        'stock' => 'decimal:2',
        'costo' => 'decimal:2',
        'precio' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $withCount = ['abiertos'];

    public function setDescripcionAttribute($value)
    {
        $this->attributes['descripcion'] = mb_strtoupper($value, 'UTF-8');
    }

    public function setDescripcionInternaAttribute($value)
    {
        $this->attributes['descripcion_interna'] = mb_strtoupper($value, 'UTF-8');
    }


    public function categoria()
    {
        return $this->belongsTo(ArticuloCategoria::class, 'categoria_id');
    }

    public function marca()
    {
        return $this->belongsTo(ArticuloMarca::class, 'marca_id');
    }

    public function unidad()
    {
        return $this->belongsTo(ArticuloUnidad::class, 'unidad_id');
    }

    public function abiertos()
    {
        return $this->hasMany(ArticuloAbierto::class);
    }

    public function salidas()
    {
        return $this->hasMany(ArticuloSalida::class);
    }
}
