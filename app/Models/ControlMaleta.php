<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlMaleta extends Model
{
    use HasFactory;

    protected $table = 'control_maletas';

    protected $fillable = [
        'maleta_id',
        'fecha',
        'responsable_id',
        'propietario_id',
        'evidencia_url',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function maleta()
    {
        return $this->belongsTo(Maleta::class, 'maleta_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function propietario()
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    public function detalles()
    {
        return $this->hasMany(ControlMaletaDetalle::class, 'control_maleta_id');
    }
}
