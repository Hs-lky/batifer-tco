<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const filename = ref('Batifer_TCO_Export');

function downloadCsv() {
    const name = filename.value.trim() || 'Batifer_TCO_Export';
    const clean = name.replace(/[^a-zA-Z0-9_\-]/g, '_');
    window.location.href = '/export/csv?filename=' + encodeURIComponent(clean);
}

function downloadJson() {
    window.location.href = '/export/json';
}
</script>

<template>
    <div class="flex-1 bg-bg p-5 overflow-y-auto">
        <h2 class="font-mono text-[11px] font-bold text-navy tracking-[2px] uppercase mb-2">
            📥 Export des Données
        </h2>
        <p class="text-[10px] text-muted mb-5 leading-relaxed max-w-2xl">
            Exportez l'ensemble des dossiers avec leurs frais d'approche et produits au format CSV 
            (compatible Excel) ou JSON. Le CSV inclut toutes les colonnes de calcul : prix MAD, 
            chaque ligne de coût, TCO, ratio FA, et la décomposition des produits.
        </p>

        <div class="bg-card border border-border rounded-lg p-5 max-w-lg">
            <!-- Filename input -->
            <div class="mb-4">
                <label class="text-[11px] text-muted font-medium block mb-1">Nom du fichier</label>
                <div class="flex items-center gap-2">
                    <input
                        v-model="filename"
                        type="text"
                        placeholder="Batifer_TCO_Export"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-2 px-2.5 focus:border-accent outline-none transition-colors flex-1"
                    />
                    <span class="text-[10px] text-muted font-mono shrink-0">.csv</span>
                </div>
            </div>

            <!-- Format description -->
            <div class="mb-4 p-3 bg-[#fafbfd] border border-border/50 rounded-md">
                <div class="text-[10px] text-muted font-medium mb-1.5">Format CSV inclus :</div>
                <ul class="text-[10px] text-text space-y-0.5 list-disc list-inside">
                    <li>Identifiants (ref, fournisseur, pays, incoterm, famille, devise)</li>
                    <li>Prix d'achat (devise, taux, MAD, quantité)</li>
                    <li>Fret maritime (montant, devise, taux)</li>
                    <li>14 lignes de frais d'approche (Fret, Assurance, Douane, Transit, Bancaire, Avis, Manutention, Acconage, TIC, Transport Local, Magasinage, Ad Valorem, Autres, Additionnels)</li>
                    <li>Totaux calculés (Frais Total, TCO, Base FA, Ratio FA)</li>
                    <li>Produits du dossier (description, quantité, unité, ratio)</li>
                    <li>Notes et date de création</li>
                </ul>
            </div>

            <!-- Download buttons -->
            <div class="flex gap-3">
                <button
                    @click="downloadCsv"
                    class="flex-1 py-2.5 rounded-lg bg-gradient-to-br from-navy to-[#1e4a8a] text-white shadow font-sans text-xs font-bold cursor-pointer border-none hover:shadow-md transition-shadow"
                >
                    📥 Télécharger CSV
                </button>
                <button
                    @click="downloadJson"
                    class="flex-1 py-2.5 rounded-lg bg-white border border-border text-text hover:bg-[#f8fafc] font-sans text-xs font-semibold cursor-pointer transition-colors"
                >
                    📋 Télécharger JSON
                </button>
            </div>
        </div>
    </div>
</template>
