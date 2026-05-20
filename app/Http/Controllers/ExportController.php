<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const COSTS_DEF = [
        'fret' => 'Fret Maritime',
        'assur' => 'Assurance',
        'douane' => 'Frais douane',
        'transit' => 'Transit / Transitaire',
        'banque' => 'Frais Bancaires',
        'avis' => 'Avis Arrivee',
        'manut' => 'Manutention Portuaire',
        'acconage' => 'Acconage',
        'tic' => 'TIC (Taxe Interieure)',
        'tlocal' => 'Transport Local',
        'magasin' => 'Magasinage',
        'adval' => 'Ad Valorem',
        'autre1' => 'Autres Frais',
        'autre2' => 'Frais Additionnels',
    ];

    private const HEADER_INCO = ['EXW', 'FOB', 'FCA', 'FAS'];

    public function csv(): StreamedResponse
    {
        $dossiers = Dossier::with(['couts', 'produits'])
            ->orderBy('created_at')
            ->get();

        $filename = request()->get('filename', 'Batifer_TCO_Export');
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($dossiers) {
            $handle = fopen('php://output', 'w');

            // Write BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            $headerRow = [
                'ID',
                'Ref Dossier',
                'Fournisseur',
                'Pays',
                'Incoterm',
                'Famille',
                'Devise',
                'Unite',
                'Cert Origine',
                'Prix Devise',
                'Taux Change',
                'Quantite',
                'Prix MAD',
                'Fret Montant Orig',
                'Fret Devise Orig',
                'Fret Taux Orig',
            ];

            foreach (self::COSTS_DEF as $label) {
                $headerRow[] = $label . ' (MAD)';
            }

            $headerRow[] = 'Frais Total';
            $headerRow[] = 'TCO';
            $headerRow[] = 'Base FA';
            $headerRow[] = 'Ratio FA (%)';
            $headerRow[] = 'Produits (desc;qte;unite;ratio)';
            $headerRow[] = 'Notes';
            $headerRow[] = 'Date Creation';

            fputcsv($handle, $headerRow, ';');

            foreach ($dossiers as $dossier) {
                $pxMad = (float) $dossier->px_devise * (float) $dossier->taux;

                $costMap = [];
                foreach ($dossier->couts as $c) {
                    $costMap[$c->cout_id] = (float) $c->montant;
                }

                $fret = 0;
                if (in_array($dossier->incoterm, self::HEADER_INCO, true)) {
                    if ((float) $dossier->fret_montant_orig > 0) {
                        $fret = round((float) $dossier->fret_montant_orig * (float) $dossier->fret_taux_orig, 2);
                    }
                }
                $costMap['fret'] = $fret;

                $fraisTotal = array_sum($costMap);
                $tco = $pxMad + $fraisTotal;
                $frais = $fraisTotal - $fret;
                $baseFA = $pxMad + $fret;
                $ratioFa = $baseFA > 0 ? round(($frais / $baseFA) * 100, 1) : 0;

                $row = [
                    $dossier->id,
                    $dossier->ref,
                    $dossier->frs,
                    $dossier->pays ?? '',
                    $dossier->incoterm,
                    $dossier->famille ?? '',
                    $dossier->devise,
                    $dossier->unite ?? '',
                    $dossier->cert_origine ?? '',
                    number_format((float) $dossier->px_devise, 2, ',', ''),
                    number_format((float) $dossier->taux, 2, ',', ''),
                    number_format((float) $dossier->qte, 2, ',', ''),
                    number_format($pxMad, 2, ',', ''),
                    number_format((float) $dossier->fret_montant_orig, 2, ',', ''),
                    $dossier->fret_devise_orig ?? '',
                    number_format((float) $dossier->fret_taux_orig, 2, ',', ''),
                ];

                foreach (array_keys(self::COSTS_DEF) as $costId) {
                    $row[] = number_format($costMap[$costId] ?? 0, 2, ',', '');
                }

                $produitsStr = '';
                foreach ($dossier->produits as $prod) {
                    $produitsStr .= $prod->description . ';' . $prod->quantite . ';' . ($prod->unite ?? 'KG') . ';' . $prod->ratio . ' | ';
                }
                $produitsStr = rtrim($produitsStr, ' | ');

                $row[] = number_format($fraisTotal, 2, ',', '');
                $row[] = number_format($tco, 2, ',', '');
                $row[] = number_format($baseFA, 2, ',', '');
                $row[] = number_format($ratioFa, 1, ',', '');
                $row[] = $produitsStr;
                $row[] = $dossier->notes ?? '';
                $row[] = $dossier->created_at ? $dossier->created_at->format('d/m/Y H:i') : '';

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function json(): JsonResponse
    {
        $dossiers = Dossier::with(['couts', 'produits'])
            ->orderBy('created_at')
            ->get();

        $data = $dossiers->map(function ($dossier) {
            $pxMad = (float) $dossier->px_devise * (float) $dossier->taux;

            $costMap = [];
            foreach ($dossier->couts as $c) {
                $costMap[$c->cout_id] = (float) $c->montant;
            }

            $fret = 0;
            if (in_array($dossier->incoterm, self::HEADER_INCO, true)) {
                if ((float) $dossier->fret_montant_orig > 0) {
                    $fret = round((float) $dossier->fret_montant_orig * (float) $dossier->fret_taux_orig, 2);
                }
            }
            $costMap['fret'] = $fret;

            $fraisTotal = array_sum($costMap);
            $tco = $pxMad + $fraisTotal;
            $frais = $fraisTotal - $fret;
            $baseFA = $pxMad + $fret;
            $ratioFa = $baseFA > 0 ? round(($frais / $baseFA) * 100, 1) : 0;

            return [
                'id' => $dossier->id,
                'ref' => $dossier->ref,
                'frs' => $dossier->frs,
                'pays' => $dossier->pays,
                'incoterm' => $dossier->incoterm,
                'famille' => $dossier->famille,
                'devise' => $dossier->devise,
                'unite' => $dossier->unite,
                'cert_origine' => $dossier->cert_origine,
                'px_devise' => (float) $dossier->px_devise,
                'taux' => (float) $dossier->taux,
                'qte' => (float) $dossier->qte,
                'px_mad' => round($pxMad, 2),
                'fret_montant_orig' => (float) $dossier->fret_montant_orig,
                'fret_devise_orig' => $dossier->fret_devise_orig,
                'fret_taux_orig' => (float) $dossier->fret_taux_orig,
                'costs' => $costMap,
                'frais_total' => round($fraisTotal, 2),
                'tco' => round($tco, 2),
                'base_fa' => round($baseFA, 2),
                'ratio_fa' => $ratioFa,
                'produits' => $dossier->produits->map(fn($p) => [
                    'description' => $p->description,
                    'quantite' => $p->quantite,
                    'unite' => $p->unite,
                    'ratio' => $p->ratio,
                ]),
                'notes' => $dossier->notes,
                'created_at' => $dossier->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'dossiers' => $data,
            'count' => $data->count(),
        ]);
    }
}
