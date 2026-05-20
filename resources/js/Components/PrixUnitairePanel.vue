<script setup>
import { computed } from 'vue';
import { fmt, faStatus } from '../lib/formatters';
import { DEFAULT_RATIO_SEUILS } from '../lib/constants';

const props = defineProps({
    analysis: { type: Object, required: true },
    ratioSeuils: { type: Object, default: () => ({ ...DEFAULT_RATIO_SEUILS }) },
});

const produits = computed(() => props.analysis.produits || null);
const hasProducts = computed(() => Array.isArray(produits.value) && produits.value.length > 0);

const pxMad = computed(() => props.analysis.px_mad || 0);
const tco = computed(() => props.analysis.tco || 0);

function puAchat(qte) {
    return qte > 0 ? pxMad.value / qte : 0;
}

function puTco(qte) {
    return qte > 0 ? tco.value / qte : 0;
}

function surcout(puAchatVal, puTcoVal) {
    return Math.max(0, puTcoVal - puAchatVal);
}

function surcoutRatio(puAchatVal, puTcoVal) {
    if (puAchatVal <= 0) return 0;
    return ((puTcoVal - puAchatVal) / puAchatVal) * 100;
}

function surcoutStatus(surcoutVal, puAchatVal) {
    const ratio = surcoutRatio(puAchatVal, puAchatVal + surcoutVal);
    if (ratio <= props.ratioSeuils.ok) return { border: 'border-t-[3px] border-t-ok', color: 'text-ok' };
    if (ratio <= props.ratioSeuils.warn) return { border: 'border-t-[3px] border-t-warn', color: 'text-warn' };
    return { border: 'border-t-[3px] border-t-danger', color: 'text-danger' };
}

const defaultQte = computed(() => {
    if (hasProducts.value && produits.value.length === 1) return Number(produits.value[0].qte) || 0;
    return 1;
});
</script>

<template>
    <div class="bg-card border border-border rounded-[11px] p-4 shadow">
        <h3 class="text-xs font-mono font-bold text-navy tracking-wider uppercase mb-4">
            Prix Unitaire
        </h3>

        <!-- Multi-product mode -->
        <div v-if="hasProducts" class="space-y-3.5">
            <div
                v-for="(p, idx) in produits"
                :key="idx"
                class="bg-card border border-border rounded-[11px] overflow-hidden shadow-sm"
            >
                <!-- Product header -->
                <div class="px-3.5 py-2.5 border-b border-border flex items-center justify-between bg-[#f8fafd]">
                    <span class="text-[11px] font-bold text-text">{{ p.name || 'Produit ' + (idx + 1) }}</span>
                    <div class="flex items-center gap-4 text-[10px] font-mono text-muted">
                        <span v-if="(p.qte || 0) > 0">Qté: <strong class="text-text">{{ p.qte }}</strong></span>
                        <span v-else class="text-warn font-bold">Quantité non renseignée</span>
                        <span v-if="p.ratio !== undefined">
                            Ratio: <strong :style="{ color: faStatus(p.ratio, ratioSeuils).color }">{{ p.ratio.toFixed(1) }}%</strong>
                        </span>
                    </div>
                </div>

                <!-- 3-column grid -->
                <div class="grid grid-cols-3 gap-0">
                    <!-- PU Achat -->
                    <div class="border-t-[3px] border-t-[#3b82f6] rounded-t-[6px] p-3 text-center">
                        <div class="text-[9px] font-mono text-muted tracking-wider uppercase mb-1">PU Achat</div>
                        <div v-if="(p.qte || 0) > 0" class="text-[13px] font-mono font-bold text-text">
                            {{ fmt(puAchat(p.qte || 1)) }}
                        </div>
                        <div v-else class="text-[10px] text-muted italic">
                            —
                        </div>
                    </div>

                    <!-- PU TCO -->
                    <div class="border-t-[3px] border-t-accent rounded-t-[6px] p-3 text-center">
                        <div class="text-[9px] font-mono text-muted tracking-wider uppercase mb-1">PU TCO</div>
                        <div v-if="(p.qte || 0) > 0" class="text-[13px] font-mono font-bold text-text">
                            {{ fmt(puTco(p.qte || 1)) }}
                        </div>
                        <div v-else class="text-[10px] text-muted italic">
                            —
                        </div>
                    </div>

                    <!-- Surcoût -->
                    <div
                        class="rounded-t-[6px] p-3 text-center"
                        :class="surcoutStatus(surcout(puAchat(p.qte || 1), puTco(p.qte || 1)), puAchat(p.qte || 1)).border"
                    >
                        <div class="text-[9px] font-mono text-muted tracking-wider uppercase mb-1">Surcoût</div>
                        <div v-if="(p.qte || 0) > 0" class="text-[13px] font-mono font-bold" :class="surcoutStatus(surcout(puAchat(p.qte || 1), puTco(p.qte || 1)), puAchat(p.qte || 1)).color">
                            {{ fmt(surcout(puAchat(p.qte || 1), puTco(p.qte || 1))) }}
                        </div>
                        <div v-else class="text-[10px] text-muted italic">
                            —
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mono-product mode -->
        <div v-else class="grid grid-cols-3 gap-0">
            <!-- PU Achat -->
            <div class="border-t-[3px] border-t-[#3b82f6] rounded-t-[6px] p-3.5 text-center bg-card border border-border rounded-[11px] shadow-sm">
                <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1.5">PU Achat</div>
                <div v-if="defaultQte > 0" class="text-[15px] font-mono font-bold text-text">
                    {{ fmt(puAchat(defaultQte)) }}
                </div>
                <div v-else class="text-[11px] text-warn font-bold italic">
                    Quantité non renseignée
                </div>
                <div class="text-[9px] text-muted mt-1">par unité</div>
            </div>

            <!-- PU TCO -->
            <div class="border-t-[3px] border-t-accent rounded-t-[6px] p-3.5 text-center bg-card border border-border rounded-[11px] shadow-sm">
                <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1.5">PU TCO</div>
                <div v-if="defaultQte > 0" class="text-[15px] font-mono font-bold text-text">
                    {{ fmt(puTco(defaultQte)) }}
                </div>
                <div v-else class="text-[11px] text-warn font-bold italic">
                    Quantité non renseignée
                </div>
                <div class="text-[9px] text-muted mt-1">par unité</div>
            </div>

            <!-- Surcoût -->
            <div
                class="rounded-t-[6px] p-3.5 text-center bg-card border border-border rounded-[11px] shadow-sm"
                :class="surcoutStatus(surcout(puAchat(defaultQte), puTco(defaultQte)), puAchat(defaultQte)).border"
            >
                <div class="text-[10px] font-mono text-muted tracking-wider uppercase mb-1.5">Surcoût</div>
                <div v-if="defaultQte > 0" class="text-[15px] font-mono font-bold" :class="surcoutStatus(surcout(puAchat(defaultQte), puTco(defaultQte)), puAchat(defaultQte)).color">
                    {{ fmt(surcout(puAchat(defaultQte), puTco(defaultQte))) }}
                </div>
                <div v-else class="text-[11px] text-warn font-bold italic">
                    Quantité non renseignée
                </div>
                <div class="text-[9px] text-muted mt-1">par unité</div>
            </div>
        </div>
    </div>
</template>
