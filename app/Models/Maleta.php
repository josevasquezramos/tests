<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maleta extends Model
{
    use HasFactory;

    protected $table = 'maletas';

    protected $fillable = ['codigo', 'propietario_id'];

    public function propietario()
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    public function detalles()
    {
        return $this->hasMany(MaletaDetalle::class, 'maleta_id');
    }

    public function controles()
    {
        return $this->hasMany(ControlMaleta::class, 'maleta_id');
    }
}
