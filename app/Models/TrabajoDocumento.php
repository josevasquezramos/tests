<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TrabajoDocumento extends Pivot
{
    use HasFactory;

    protected $table = 'trabajo_documentos';

    protected $fillable = [
        'trabajo_id',
        'documento_id',
    ];
}
