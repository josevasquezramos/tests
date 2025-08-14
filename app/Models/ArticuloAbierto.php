<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticuloAbierto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'articulo_abiertos';

    protected $fillable = [
        'articulo_id',
        'restante'
    ];

    protected $casts = [
        'restante' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function salidas()
    {
        return $this->hasMany(ArticuloSalida::class);
    }
}
