<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { COSTS_DEF, INCO_BATIFER_FRET, PAYS_OPTIONS, FAMILLE_OPTIONS, DEVISE_OPTIONS, INCO_OPTIONS, UNITES } from '../lib/constants';
import { useCalculator } from '../composables/useCalculator';

const { form, costInputs, analysis } = useCalculator();

// Set initial defaults beyond composable
form.taux = 10.72;
form.devise = 'EUR';

// ─── Additional local state ─────────────────────────────────────────────
const numDossier = ref('');
const fournisseur = ref('');
const quantite = ref(0);
const unite = ref('KG');
const observations = ref('');

// ─── Multi-produits state ───────────────────────────────────────────────
const produits = ref([]);

function addProduit() {
    produits.value.push({
        id: Date.now(),
        description: '',
        quantite: 0,
        unite: 'KG',
        ratio: 0,
    });
}

function removeProduit(id) {
    produits.value = produits.value.filter((p) => p.id !== id);
}

// ─── Computed ───────────────────────────────────────────────────────────
const isFretIncoterm = computed(() => INCO_BATIFER_FRET.includes(form.incoterm));

const fretConversionMAD = computed(() => {
    if (!isFretIncoterm.value || form.fret_devise === 'MAD') return null;
    return Math.round(form.fret_montant * form.fret_taux * 100) / 100;
});

const costsExcludingFret = computed(() =>
    COSTS_DEF.filter((c) => c.id !== 'fret')
);

const produitsRatioTotal = computed(() =>
    produits.value.reduce((sum, p) => sum + (Number(p.ratio) || 0), 0)
);

const ratioWarning = computed(() =>
    produits.value.length > 0 && Math.abs(produitsRatioTotal.value - 100) > 0.01
);

// ─── Watch incoterm to auto-set certOrigine for ALE countries ──────────
watch(() => form.incoterm, (val) => {
    // Keep existing value unless switching to non-applicable
    if (val && !isFretIncoterm.value && form.fret_montant > 0) {
        // fret is not applicable, but we keep the value - just hide the section
    }
});

// ─── Save handler ───────────────────────────────────────────────────────
function handleSave() {
    const payload = {
        num_dossier: numDossier.value,
        fournisseur: fournisseur.value,
        ...form,
        quantite: quantite.value,
        unite: unite.value,
        observations: observations.value,
        costs: { ...costInputs },
        produits: produits.value.length > 0 ? produits.value : null,
    };
    router.post('/dossiers', payload, {
        onSuccess: () => {
            // Reset form or redirect handled by server
        },
    });
}

function handleAnalyze() {
    if (!numDossier.value) return;
    router.get('/analyse', { dossier: numDossier.value });
}

function handleReset() {
    numDossier.value = '';
    fournisseur.value = '';
    form.px_devise = 0;
    form.taux = 1;
    form.incoterm = 'CFR';
    form.pays = '';
    form.certOrigine = 'non';
    form.fret_montant = 0;
    form.fret_devise = 'MAD';
    form.fret_taux = 1;
    form.multiActive = false;
    quantite.value = 0;
    unite.value = 'KG';
    observations.value = '';
    produits.value = [];
    Object.keys(costInputs).forEach((k) => { costInputs[k] = 0; });
}
</script>

<template>
    <aside class="w-80 min-w-80 bg-card border-r border-border overflow-y-auto shadow">
        <!-- ════════════════════════════════════════════════════════════════
             SECTION 1: Identification Dossier
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 border-b border-border bg-white">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Identification Dossier
            </h2>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">N° Dossier</label>
                    <input
                        v-model="numDossier"
                        type="text"
                        placeholder="ex: 001/26"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Fournisseur</label>
                    <input
                        v-model="fournisseur"
                        type="text"
                        placeholder="Nom"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Pays d'Origine</label>
                    <select
                        v-model="form.pays"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option value="" disabled>Sélectionner...</option>
                        <option v-for="p in PAYS_OPTIONS" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Incoterm</label>
                    <select
                        v-model="form.incoterm"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option v-for="inc in INCO_OPTIONS" :key="inc.value" :value="inc.value">{{ inc.label }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Famille Produit</label>
                    <select
                        v-model="form.famille"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option value="" disabled>Sélectionner...</option>
                        <option v-for="f in FAMILLE_OPTIONS" :key="f.value" :value="f.value">{{ f.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Devise</label>
                    <select
                        v-model="form.devise"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option v-for="d in DEVISE_OPTIONS" :key="d.value" :value="d.value">{{ d.label }}</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 2: Prix d'Achat
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 border-b border-border bg-[#fafbfd]">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Prix d'Achat
            </h2>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">
                        Prix <span class="text-navy font-bold">{{ form.devise || 'EUR' }}</span>
                    </label>
                    <input
                        v-model.number="form.px_devise"
                        type="number"
                        step="0.01"
                        min="0"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Taux Change</label>
                    <input
                        v-model.number="form.taux"
                        type="number"
                        step="0.01"
                        min="0"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Quantité</label>
                    <input
                        v-model.number="quantite"
                        type="number"
                        step="0.01"
                        min="0"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Unité</label>
                    <select
                        v-model="unite"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option v-for="u in UNITES" :key="u" :value="u">{{ u }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-[11px] text-muted font-medium block mb-1">Certificat d'Origine</label>
                <select
                    v-model="form.certOrigine"
                    class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                >
                    <option value="non">Non</option>
                    <option value="oui">Oui ✅</option>
                    <option value="na">N/A</option>
                </select>
            </div>
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 3: Fret (conditional)
             ════════════════════════════════════════════════════════════════ -->
        <section v-if="isFretIncoterm" class="p-4 border-b border-border bg-white">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-1">
                Fret
                <span class="inline-block ml-1 px-1.5 py-px rounded bg-accent/10 text-accent text-[9px] font-bold">
                    {{ form.incoterm }}
                </span>
            </h2>
            <p class="text-[10px] text-muted mb-3">saisir le fret payé</p>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Montant Fret</label>
                    <input
                        v-model.number="form.fret_montant"
                        type="number"
                        step="0.01"
                        min="0"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                    />
                </div>
                <div>
                    <label class="text-[11px] text-muted font-medium block mb-1">Devise Fret</label>
                    <select
                        v-model="form.fret_devise"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                    >
                        <option>MAD</option>
                        <option>EUR</option>
                        <option>USD</option>
                        <option>GBP</option>
                    </select>
                </div>
            </div>

            <!-- Conversion when fret devise != MAD -->
            <template v-if="form.fret_devise !== 'MAD' && form.fret_montant > 0">
                <div class="grid grid-cols-[1fr_auto] gap-2 items-end mb-2">
                    <div>
                        <label class="text-[11px] text-muted font-medium block mb-1">Taux {{ form.fret_devise }} → MAD</label>
                        <input
                            v-model.number="form.fret_taux"
                            type="number"
                            step="0.01"
                            min="0"
                            class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                        />
                    </div>
                </div>
                <div v-if="fretConversionMAD !== null" class="text-xs font-mono text-accent font-semibold bg-accent/5 px-2 py-1 rounded">
                    = {{ fretConversionMAD.toFixed(2) }} MAD
                </div>
            </template>
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 4: Frais Approches (MAD)
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 border-b border-border" :class="isFretIncoterm ? 'bg-[#fafbfd]' : 'bg-white'">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Frais Approches (MAD)
            </h2>

            <div
                v-for="cost in costsExcludingFret"
                :key="cost.id"
                class="grid grid-cols-[1fr_90px] items-center gap-2 mb-2"
            >
                <div class="flex items-center gap-1.5">
                    <span
                        class="w-1.5 h-1.5 rounded-full shrink-0"
                        :style="{ backgroundColor: cost.color }"
                    />
                    <label class="text-[11px] text-muted font-medium truncate">{{ cost.label }}</label>
                </div>
                <div class="flex items-center gap-1">
                    <input
                        v-model.number="costInputs[cost.id]"
                        type="number"
                        step="0.01"
                        min="0"
                        class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors text-right"
                    />
                    <span class="text-[10px] text-muted font-mono shrink-0">MAD</span>
                </div>
            </div>
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 5: Multi-Produits
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 border-b border-border bg-white">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3 flex items-center gap-2">
                🔢 Produits du Dossier
                <label class="flex items-center gap-1 ml-auto cursor-pointer select-none">
                    <input
                        v-model="form.multiActive"
                        type="checkbox"
                        class="w-3.5 h-3.5 rounded border-border text-accent focus:ring-accent cursor-pointer"
                    />
                    <span class="text-[10px] text-muted font-medium tracking-normal normal-case">Multi-produits</span>
                </label>
            </h2>

            <template v-if="!form.multiActive">
                <p class="text-[11px] text-muted italic leading-relaxed">
                    Mode mono-produit actif. Utilisez la <strong>Quantité</strong> et l'<strong>Unité</strong> de la section Prix d'Achat.
                </p>
            </template>

            <template v-else>
                <!-- Produit rows -->
                <div
                    v-for="(produit, idx) in produits"
                    :key="produit.id"
                    class="mb-3 p-3 border border-border rounded-lg bg-[#fafbfd]"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-mono font-bold text-navy tracking-wide">
                            PRODUIT {{ idx + 1 }}
                        </span>
                        <button
                            @click="removeProduit(produit.id)"
                            class="text-[10px] text-danger hover:underline font-medium"
                        >
                            Supprimer
                        </button>
                    </div>

                    <div class="mb-2">
                        <label class="text-[11px] text-muted font-medium block mb-1">Description</label>
                        <input
                            v-model="produit.description"
                            type="text"
                            placeholder="ex: Tôle acier 2mm"
                            class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <div>
                            <label class="text-[11px] text-muted font-medium block mb-1">Qté</label>
                            <input
                                v-model.number="produit.quantite"
                                type="number"
                                step="0.01"
                                min="0"
                                class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                            />
                        </div>
                        <div>
                            <label class="text-[11px] text-muted font-medium block mb-1">Unité</label>
                            <select
                                v-model="produit.unite"
                                class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors appearance-none"
                            >
                                <option v-for="u in UNITES" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] text-muted font-medium block mb-1">Ratio (%)</label>
                            <input
                                v-model.number="produit.ratio"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors"
                            />
                        </div>
                    </div>
                </div>

                <button
                    @click="addProduit"
                    class="w-full text-center text-xs text-accent font-semibold py-2 border border-dashed border-accent/30 rounded-md bg-accent/5 hover:bg-accent/10 transition-colors cursor-pointer mb-2"
                >
                    + Ajouter un produit
                </button>

                <div
                    v-if="ratioWarning"
                    class="text-[10px] text-danger font-semibold bg-danger/5 border border-danger/20 rounded px-2 py-1.5"
                >
                    ⚠️ La somme des ratios est de {{ produitsRatioTotal.toFixed(1) }}%. Elle doit être égale à 100%.
                </div>
            </template>
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 6: Notes
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 border-b border-border bg-[#fafbfd]">
            <h2 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Notes / Observations
            </h2>
            <textarea
                v-model="observations"
                rows="3"
                placeholder="Notes, observations ou commentaires sur ce dossier..."
                class="bg-[#f8fafc] border border-border rounded-md text-text font-mono text-xs py-1.5 px-2 focus:border-accent w-full outline-none transition-colors resize-y"
            />
        </section>

        <!-- ════════════════════════════════════════════════════════════════
             SECTION 7: Action Buttons
             ════════════════════════════════════════════════════════════════ -->
        <section class="p-4 bg-white flex flex-col gap-2">
            <button
                @click="handleSave"
                class="w-full py-2.5 rounded-lg bg-gradient-to-br from-navy to-[#1e4a8a] text-white shadow font-sans text-xs font-bold cursor-pointer border-none hover:shadow-md transition-shadow"
            >
                💾 Enregistrer le Dossier
            </button>
            <button
                @click="handleAnalyze"
                class="w-full py-2.5 rounded-lg bg-transparent border border-border text-muted hover:text-text font-sans text-xs font-semibold cursor-pointer transition-colors"
            >
                📊 Analyser →
            </button>
            <button
                @click="handleReset"
                class="w-full py-2.5 rounded-lg bg-transparent border border-border text-muted hover:text-text font-sans text-xs font-semibold cursor-pointer transition-colors"
            >
                🔄 Nouveau Dossier
            </button>
        </section>
    </aside>
</template>
