<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaletaDetalle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'maleta_detalles';

    protected $fillable = [
        'maleta_id',
        'herramienta_id',
        'ultimo_estado',
    ];

    protected $casts = [
        'maleta_id' => 'integer',
        'ultimo_estado' => 'string',
        'deleted_at' => 'datetime',
    ];

    public function maleta()
    {
        return $this->belongsTo(Maleta::class, 'maleta_id');
    }

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }

    public function controlDetalles()
    {
        return $this->hasMany(ControlMaletaDetalle::class, 'maleta_detalle_id');
    }

    public function incidencias()
    {
        return $this->hasMany(HerramientaIncidencia::class, 'maleta_detalle_id');
    }
}
