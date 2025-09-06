<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HerramientaIncidencia extends Model
{
    use HasFactory;

    protected $table = 'herramienta_incidencias';

    protected $fillable = [
        'fecha',
        'tipo_origen',
        'maleta_detalle_id',
        'herramienta_id',
        'cantidad',
        'propietario_id',
        'responsable_id',
        'motivo',
        'prev_estado',
        'prev_deleted_at',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'tipo_origen' => 'string',
        'motivo' => 'string',
        'prev_estado' => 'string',
        'prev_deleted_at' => 'datetime',
        'cantidad' => 'integer',
    ];

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }

    public function maletaDetalle()
    {
        return $this->belongsTo(MaletaDetalle::class, 'maleta_detalle_id');
    }

    public function propietario()
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
