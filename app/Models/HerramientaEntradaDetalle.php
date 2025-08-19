<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HerramientaEntradaDetalle extends Model
{
    use HasFactory;

    protected $table = 'herramienta_entrada_detalles';

    protected $fillable = [
        'herramienta_entrada_id',
        'herramienta_id',
        'cantidad',
        'costo',
    ];

    protected $casts = [
        'herramienta_entrada_id' => 'integer',
        'herramienta_id' => 'integer',
        'cantidad' => 'integer',
        'costo' => 'decimal:2',
    ];

    public function entrada()
    {
        return $this->belongsTo(HerramientaEntrada::class, 'herramienta_entrada_id');
    }

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }
}
