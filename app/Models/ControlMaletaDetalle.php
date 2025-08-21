<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlMaletaDetalle extends Model
{
    use HasFactory;

    protected $table = 'control_maleta_detalles';

    protected $fillable = [
        'control_maleta_id',
        'maleta_detalle_id',
        'herramienta_id',
        'estado',
        'observacion',
        'prev_estado',
        'prev_deleted_at',
    ];

    protected $casts = [
        'estado' => 'string',
        'prev_estado' => 'string',
        'prev_deleted_at' => 'datetime',
    ];

    public function control()
    {
        return $this->belongsTo(ControlMaleta::class, 'control_maleta_id');
    }

    public function maletaDetalle()
    {
        return $this->belongsTo(MaletaDetalle::class, 'maleta_detalle_id');
    }

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }
}
