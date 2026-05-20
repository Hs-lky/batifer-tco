<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoutDossier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dossier_id',
        'cout_id',
        'montant',
    ];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
