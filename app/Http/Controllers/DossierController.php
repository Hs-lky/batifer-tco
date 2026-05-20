<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\CoutDossier;
use App\Models\Produit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DossierController extends Controller
{
    private function userId(): string
    {
        return session()->get('user_id', 'anonymous');
    }

    public function index()
    {
        $dossiers = Dossier::with(['produits', 'couts'])
            ->where('user_id', $this->userId())
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Dossiers', ['dossiers' => $dossiers]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ref' => 'required|string|max:50',
            'frs' => 'required|string|max:100',
            'pays' => 'nullable|string|max:50',
            'incoterm' => 'required|string|max:3',
            'famille' => 'nullable|string|max:50',
            'devise' => 'required|string|max:3',
            'unite' => 'nullable|string|max:20',
            'cert_origine' => 'nullable|string|max:10',
            'px_devise' => 'nullable|numeric',
            'taux' => 'nullable|numeric',
            'qte' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'fret_montant_orig' => 'nullable|numeric',
            'fret_devise_orig' => 'nullable|string|max:3',
            'fret_taux_orig' => 'nullable|numeric',
            'costs' => 'nullable|array',
            'produits' => 'nullable|array',
        ]);

        $validated['user_id'] = $this->userId();
        $dossier = Dossier::create($validated);

        if ($request->has('costs')) {
            foreach ($request->costs as $coutId => $montant) {
                CoutDossier::create([
                    'dossier_id' => $dossier->id,
                    'cout_id' => $coutId,
                    'montant' => $montant ?? 0,
                ]);
            }
        }

        if ($request->has('produits')) {
            foreach ($request->produits as $p) {
                Produit::create([
                    'dossier_id' => $dossier->id,
                    'description' => $p['desc'] ?? '',
                    'quantite' => $p['qte'] ?? 0,
                    'unite' => $p['unite'] ?? 'KG',
                    'ratio' => $p['ratio'] ?? 0,
                ]);
            }
        }

        return redirect()->route('dossiers.index')
            ->with('success', '✅ Dossier "' . $dossier->ref . '" enregistré !');
    }

    public function show(string $id)
    {
        $dossier = Dossier::with(['produits', 'couts'])
            ->where('user_id', $this->userId())
            ->findOrFail($id);

        $costs = (object) [];
        foreach ($dossier->couts as $c) {
            $costs->{$c->cout_id} = (float) $c->montant;
        }

        return Inertia::render('Saisie', [
            'dossier' => array_merge($dossier->toArray(), ['costs' => $costs]),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $dossier = Dossier::where('user_id', $this->userId())->findOrFail($id);

        $validated = $request->validate([
            'ref' => 'required|string|max:50',
            'frs' => 'required|string|max:100',
            'pays' => 'nullable|string|max:50',
            'incoterm' => 'required|string|max:3',
            'famille' => 'nullable|string|max:50',
            'devise' => 'required|string|max:3',
            'unite' => 'nullable|string|max:20',
            'cert_origine' => 'nullable|string|max:10',
            'px_devise' => 'nullable|numeric',
            'taux' => 'nullable|numeric',
            'qte' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'fret_montant_orig' => 'nullable|numeric',
            'fret_devise_orig' => 'nullable|string|max:3',
            'fret_taux_orig' => 'nullable|numeric',
            'costs' => 'nullable|array',
            'produits' => 'nullable|array',
        ]);

        $dossier->update($validated);

        CoutDossier::where('dossier_id', $dossier->id)->delete();
        if ($request->has('costs')) {
            foreach ($request->costs as $coutId => $montant) {
                CoutDossier::create([
                    'dossier_id' => $dossier->id,
                    'cout_id' => $coutId,
                    'montant' => $montant ?? 0,
                ]);
            }
        }

        Produit::where('dossier_id', $dossier->id)->delete();
        if ($request->has('produits')) {
            foreach ($request->produits as $p) {
                Produit::create([
                    'dossier_id' => $dossier->id,
                    'description' => $p['desc'] ?? '',
                    'quantite' => $p['qte'] ?? 0,
                    'unite' => $p['unite'] ?? 'KG',
                    'ratio' => $p['ratio'] ?? 0,
                ]);
            }
        }

        return redirect()->route('dossiers.index')
            ->with('success', '✅ Dossier "' . $dossier->ref . '" mis à jour !');
    }

    public function destroy(string $id)
    {
        $dossier = Dossier::where('user_id', $this->userId())->findOrFail($id);
        $dossier->delete();

        return redirect()->route('dossiers.index')
            ->with('success', '🗑 Dossier supprimé');
    }
}
