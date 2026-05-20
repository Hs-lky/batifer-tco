<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dossier extends Model
{
    protected $fillable = [
        'ref',
        'frs',
        'pays',
        'incoterm',
        'famille',
        'devise',
        'unite',
        'cert_origine',
        'px_devise',
        'taux',
        'qte',
        'notes',
        'fret_montant_orig',
        'fret_devise_orig',
        'fret_taux_orig',
        'user_id',
    ];

    public function couts(): HasMany
    {
        return $this->hasMany(CoutDossier::class);
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class);
    }
}
