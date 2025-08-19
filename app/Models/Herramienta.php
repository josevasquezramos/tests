<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Herramienta extends Model
{
    use HasFactory;

    protected $table = 'herramientas';

    protected $fillable = [
        'nombre',
        'costo',
        'stock',
        'asignadas',
        'mermas',
        'perdidas',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'stock' => 'integer',
        'asignadas' => 'integer',
        'mermas' => 'integer',
        'perdidas' => 'integer',
    ];

    public function maletaDetalles()
    {
        return $this->hasMany(MaletaDetalle::class, 'herramienta_id');
    }

    public function entradaDetalles()
    {
        return $this->hasMany(HerramientaEntradaDetalle::class, 'herramienta_id');
    }

    public function controlDetalles()
    {
        return $this->hasMany(ControlMaletaDetalle::class, 'herramienta_id');
    }
}
