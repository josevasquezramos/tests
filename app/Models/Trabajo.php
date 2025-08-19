<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Trabajo extends Model
{
    use HasFactory;

    protected $fillable = ['importe'];
    
    protected $casts = [
        'importe' => 'decimal:2',
    ];

    public function documentos(): BelongsToMany
    {
        return $this->belongsToMany(Documento::class, 'trabajo_documentos', 'trabajo_id', 'comprobante_id')
            ->withTimestamps();
    }
}
