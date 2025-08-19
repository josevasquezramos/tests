<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HerramientaEntrada extends Model
{
    use HasFactory;

    protected $table = 'herramienta_entradas';

    protected $fillable = [
        'codigo',
        'fecha',
        'observacion',
        'responsable_id',
        'evidencia_url',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function detalles()
    {
        return $this->hasMany(HerramientaEntradaDetalle::class, 'herramienta_entrada_id');
    }
}
