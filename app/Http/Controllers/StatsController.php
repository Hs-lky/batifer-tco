<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\CoutDossier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatsController extends Controller
{
    private const SEUIL_WARN = 40;
    private const SEUIL_OK = 30;

    /**
     * Aggregated statistics for the Statistiques page.
     */
    public function index()
    {
        $dossiers = Dossier::with(['couts', 'produits'])
            ->orderByDesc('created_at')
            ->get();

        // ─── Individual dossier computations ────────────────────────
        $dossierStats = $dossiers->map(function ($dossier) {
            $stats = $this->computeDossier($dossier);
            $stats['ref'] = $dossier->ref;
            $stats['frs'] = $dossier->frs;
            $stats['pays'] = $dossier->pays;
            $stats['famille'] = $dossier->famille;
            $stats['incoterm'] = $dossier->incoterm;
            $stats['cert_origine'] = $dossier->cert_origine;
            $stats['devise'] = $dossier->devise;
            $stats['created_at'] = $dossier->created_at?->toISOString();
            return $stats;
        });

        // ─── Global aggregates ──────────────────────────────────────
        $count = $dossierStats->count();
        $totalTco = $dossierStats->sum('tco');
        $totalPxMad = $dossierStats->sum('px_mad');
        $avgRatioFa = $count > 0 ? $dossierStats->avg('ratio_fa') : 0;
        $maxRatioFa = $count > 0 ? $dossierStats->max('ratio_fa') : 0;
        $minRatioFa = $count > 0 ? $dossierStats->min('ratio_fa') : 0;

        // Anomalies count (ratio_fa > seuil_warn)
        $anomaliesCount = $dossierStats->filter(fn($s) => $s['ratio_fa'] > self::SEUIL_WARN)->count();

        // Sum of inefficiencies
        $totalIneff = $dossierStats->sum('ineff_total');

        // Certificats manquants (ALE countries without cert_origine == 'oui')
        $certManquants = $dossierStats
            ->filter(fn($s) => $this->isAleCountry($s['pays']) && $s['cert_origine'] !== 'oui')
            ->count();

        // ─── Top anomalies (top 6 by ratio_fa, desc) ────────────────
        $topAnomalies = $dossierStats
            ->filter(fn($s) => $s['ratio_fa'] > self::SEUIL_OK)
            ->sortByDesc('ratio_fa')
            ->take(6)
            ->values()
            ->toArray();

        // ─── Group by pays ──────────────────────────────────────────
        $byPays = $dossierStats
            ->groupBy('pays')
            ->map(fn($group, $pays) => [
                'pays' => $pays ?: 'N/D',
                'count' => $group->count(),
                'tco' => round($group->sum('tco'), 2),
            ])
            ->sortByDesc('tco')
            ->take(10)
            ->values()
            ->toArray();

        // ─── Group by famille ───────────────────────────────────────
        $byFamille = $dossierStats
            ->groupBy('famille')
            ->map(fn($group, $famille) => [
                'famille' => $famille ?: 'N/D',
                'count' => $group->count(),
                'tco' => round($group->sum('tco'), 2),
            ])
            ->sortByDesc('tco')
            ->take(10)
            ->values()
            ->toArray();

        // ─── FA chart data (all dossiers, sorted by ratio_fa desc) ──
        $faChart = $dossierStats
            ->sortByDesc('ratio_fa')
            ->map(fn($s) => [
                'label' => $s['ref'],
                'ratio_fa' => round($s['ratio_fa'], 1),
            ])
            ->values()
            ->toArray();

        return Inertia::render('Statistiques', [
            'count' => $count,
            'totalTco' => round($totalTco, 2),
            'totalPxMad' => round($totalPxMad, 2),
            'avgRatioFa' => round($avgRatioFa, 1),
            'maxRatioFa' => round($maxRatioFa, 1),
            'minRatioFa' => round($minRatioFa, 1),
            'anomaliesCount' => $anomaliesCount,
            'totalIneff' => round($totalIneff, 2),
            'certManquants' => $certManquants,
            'topAnomalies' => $topAnomalies,
            'byPays' => $byPays,
            'byFamille' => $byFamille,
            'faChart' => $faChart,
            'seuilWarn' => self::SEUIL_WARN,
            'seuilOk' => self::SEUIL_OK,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private function computeDossier(Dossier $dossier): array
    {
        $pxMad = (float) $dossier->px_devise * (float) $dossier->taux;

        // Build costs flat map
        $costs = [];
        foreach ($dossier->couts as $c) {
            $costs[$c->cout_id] = (float) $c->montant;
        }

        // Determine fret cost
        $fret = 0;
        $incoFret = ['EXW', 'FOB', 'FCA', 'FAS'];
        if (in_array($dossier->incoterm, $incoFret, true)) {
            if ((float) $dossier->fret_montant_orig > 0) {
                $fret = round((float) $dossier->fret_montant_orig * (float) $dossier->fret_taux_orig, 2);
            }
        }
        $costs['fret'] = $fret;

        $fraisTotal = array_sum($costs);
        $tco = $pxMad + $fraisTotal;
        $frais = $fraisTotal - $fret;
        $baseFA = $pxMad + $fret;
        $ratioFa = $baseFA > 0 ? ($frais / $baseFA) * 100 : 0;

        // Inefficiency total
        $ineffIds = ['magasin', 'autre1', 'autre2'];
        $ineffTotal = 0;
        foreach ($ineffIds as $id) {
            $ineffTotal += $costs[$id] ?? 0;
        }

        return [
            'px_mad' => round($pxMad, 2),
            'tco' => round($tco, 2),
            'ratio_fa' => round($ratioFa, 1),
            'frais_total' => round($fraisTotal, 2),
            'ineff_total' => round($ineffTotal, 2),
            'fret' => $fret,
        ];
    }

    private function isAleCountry(?string $pays): bool
    {
        if (!$pays) return false;
        return in_array(strtoupper($pays), ['ESPAGNE', 'ALLEMAGNE', 'FRANCE', 'PORTUGAL', 'ITALIE', 'BELGIQUE', 'TURQUIE', 'EGYPTE'], true);
    }

    // ─── Par Produit (grouped by famille) ────────────────────────────

    public function parProduit()
    {
        $dossiers = Dossier::with(['couts', 'produits'])
            ->orderByDesc('created_at')
            ->get();

        $globalTco = 0;

        // Compute per-dossier stats first
        $dossierStats = $dossiers->map(function ($dossier) use (&$globalTco) {
            $stats = $this->computeDossier($dossier);
            $stats['id'] = $dossier->id;
            $stats['ref'] = $dossier->ref;
            $stats['frs'] = $dossier->frs;
            $stats['pays'] = $dossier->pays;
            $stats['famille'] = $dossier->famille;
            $stats['devise'] = $dossier->devise;
            $stats['qte'] = (float) $dossier->qte;
            $stats['px_devise'] = (float) $dossier->px_devise;

            // Build cost flat map
            $costMap = [];
            foreach ($dossier->couts as $c) {
                $costMap[$c->cout_id] = (float) $c->montant;
            }
            $stats['cost_map'] = $costMap;

            $globalTco += $stats['tco'];
            return $stats;
        });

        $grouped = $dossierStats->groupBy('famille');

        $groups = $grouped->map(function ($items, $famille) use ($globalTco) {
            $count = $items->count();
            $tco = round($items->sum('tco'), 2);
            $pxTotal = round($items->sum('px_mad'), 2);
            $fraisTotal = round($items->sum('frais_total'), 2);
            $faVals = $items->pluck('ratio_fa');
            $avgFa = $count > 0 ? round($faVals->avg(), 1) : 0;

            // Aggregate costs by cout_id
            $costSums = [];
            foreach ($items as $item) {
                foreach (($item['cost_map'] ?? []) as $cId => $val) {
                    $costSums[$cId] = ($costSums[$cId] ?? 0) + $val;
                }
            }
            arsort($costSums);

            // Top 5 cost items for mini bars
            $topCosts = array_slice($costSums, 0, 5, true);

            // Dossier summaries for tags
            $dossierTags = $items->map(fn($i) => [
                'ref' => $i['ref'],
                'tco' => $i['tco'],
                'ratio_fa' => $i['ratio_fa'],
            ])->sortByDesc('tco')->values()->toArray();

            return [
                'famille' => $famille ?: 'N/D',
                'dossiers' => $count,
                'tco' => $tco,
                'px' => $pxTotal,
                'frais' => $fraisTotal,
                'fa_vals' => $faVals->toArray(),
                'avg_fa' => $avgFa,
                'pct_tco' => $globalTco > 0 ? round(($tco / $globalTco) * 100, 1) : 0,
                'costs_sum' => $topCosts,
                'all_costs_sum' => $costSums,
                'dossier_tags' => $dossierTags,
            ];
        })->sortByDesc('tco')->values()->toArray();

        return Inertia::render('ParProduit', [
            'groups' => $groups,
        ]);
    }

    // ─── Par Fournisseur ─────────────────────────────────────────────

    public function parFournisseur()
    {
        $dossiers = Dossier::with(['couts', 'produits'])
            ->orderByDesc('created_at')
            ->get();

        $globalTco = 0;

        $dossierStats = $dossiers->map(function ($dossier) use (&$globalTco) {
            $stats = $this->computeDossier($dossier);
            $stats['id'] = $dossier->id;
            $stats['ref'] = $dossier->ref;
            $stats['frs'] = $dossier->frs;
            $stats['pays'] = $dossier->pays;
            $stats['famille'] = $dossier->famille;
            $stats['cert_origine'] = $dossier->cert_origine;
            $stats['incoterm'] = $dossier->incoterm;

            $costMap = [];
            foreach ($dossier->couts as $c) {
                $costMap[$c->cout_id] = (float) $c->montant;
            }
            $stats['cost_map'] = $costMap;

            $globalTco += $stats['tco'];
            return $stats;
        });

        $grouped = $dossierStats->groupBy('frs');

        $groups = $grouped->map(function ($items, $frs) use ($globalTco) {
            $count = $items->count();
            $tco = round($items->sum('tco'), 2);
            $pxTotal = round($items->sum('px_mad'), 2);
            $fraisTotal = round($items->sum('frais_total'), 2);
            $faVals = $items->pluck('ratio_fa');
            $avgFa = $count > 0 ? round($faVals->avg(), 1) : 0;

            // Anomalies: cert_origine missing for ALE countries
            $certManquants = $items->filter(fn($i) =>
                $this->isAleCountry($i['pays']) && $i['cert_origine'] !== 'oui'
            )->count();

            // Magasinage count (dossiers with magasinage cost > 0)
            $magasinageCount = $items->filter(fn($i) =>
                ($i['cost_map']['magasin'] ?? 0) > 0
            )->count();

            // Familles in this group
            $familles = $items->pluck('famille')->unique()->values()->toArray();

            // Inefficiency total
            $ineffIds = ['magasin', 'autre1', 'autre2'];
            $ineffTotal = 0;
            foreach ($items as $item) {
                foreach ($ineffIds as $id) {
                    $ineffTotal += $item['cost_map'][$id] ?? 0;
                }
            }

            // Cost sums
            $costSums = [];
            foreach ($items as $item) {
                foreach (($item['cost_map'] ?? []) as $cId => $val) {
                    $costSums[$cId] = ($costSums[$cId] ?? 0) + $val;
                }
            }
            arsort($costSums);
            $topCosts = array_slice($costSums, 0, 5, true);

            // Dossier summaries
            $dossierTags = $items->map(fn($i) => [
                'ref' => $i['ref'],
                'tco' => $i['tco'],
                'ratio_fa' => $i['ratio_fa'],
                'pays' => $i['pays'],
                'famille' => $i['famille'],
            ])->sortByDesc('tco')->values()->toArray();

            return [
                'frs' => $frs ?: 'N/D',
                'dossiers' => $count,
                'tco' => $tco,
                'px' => $pxTotal,
                'frais' => $fraisTotal,
                'fa_vals' => $faVals->toArray(),
                'avg_fa' => $avgFa,
                'pct_tco' => $globalTco > 0 ? round(($tco / $globalTco) * 100, 1) : 0,
                'costs_sum' => $topCosts,
                'cert_manquants' => $certManquants,
                'magasinage_count' => $magasinageCount,
                'familles' => $familles,
                'ineff_total' => round($ineffTotal, 2),
                'dossier_tags' => $dossierTags,
                'pays_ale' => $items->contains(fn($i) => $this->isAleCountry($i['pays'])),
            ];
        })->sortByDesc('tco')->values()->toArray();

        return Inertia::render('ParFournisseur', [
            'groups' => $groups,
        ]);
    }
}
