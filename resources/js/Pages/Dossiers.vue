<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DossierCard from '../Components/DossierCard.vue';

const props = defineProps({
    dossiers: { type: Array, required: true },
});

const search = ref('');

const filtered = computed(() => {
    if (!search.value.trim()) return props.dossiers;
    const q = search.value.toLowerCase().trim();
    return props.dossiers.filter((d) => {
        return (d.ref || '').toLowerCase().includes(q)
            || (d.frs || '').toLowerCase().includes(q)
            || (d.pays || '').toLowerCase().includes(q)
            || (d.famille || '').toLowerCase().includes(q);
    });
});

function exportJSON() {
    const blob = new Blob([JSON.stringify(props.dossiers, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'dossiers_batifer.json';
    a.click();
    URL.revokeObjectURL(a.href);
}

function exportCSV() {
    const headers = ['ref', 'frs', 'pays', 'incoterm', 'famille', 'devise', 'px_devise', 'taux', 'qte', 'unite', 'cert_origine', 'created_at'];
    const lines = [headers.join(',')];
    props.dossiers.forEach((d) => {
        const row = headers.map((h) => {
            const v = d[h];
            if (v === null || v === undefined) return '';
            const s = String(v);
            return s.includes(',') ? `"${s}"` : s;
        });
        lines.push(row.join(','));
    });
    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'dossiers_batifer.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}

function toutEffacer() {
    if (!confirm('Supprimer TOUS les dossiers ? Cette action est irréversible.')) return;
    props.dossiers.forEach((d) => {
        router.delete(route('dossiers.destroy', d.id), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                if (d.id === props.dossiers[props.dossiers.length - 1].id) {
                    router.reload();
                }
            },
        });
    });
}

function onCardDelete() {
    router.reload();
}
</script>

<template>
    <div class="flex-1 bg-bg p-5 overflow-y-auto">
        <!-- ═══ Top bar ═══ -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <div class="relative flex-1 min-w-[200px]">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par ref, fournisseur, pays, famille..."
                    class="w-full bg-card border border-border rounded-lg text-text text-xs py-2 pl-9 pr-3 outline-none focus:border-accent transition-colors"
                />
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted text-sm">🔍</span>
            </div>
            <button
                @click="exportJSON"
                class="px-3.5 py-2 rounded-lg bg-card border border-border text-muted text-[11px] font-semibold cursor-pointer hover:text-text hover:border-accent transition-colors"
            >
                📥 Export JSON
            </button>
            <button
                @click="exportCSV"
                class="px-3.5 py-2 rounded-lg bg-card border border-border text-muted text-[11px] font-semibold cursor-pointer hover:text-text hover:border-accent transition-colors"
            >
                📥 Export CSV
            </button>
            <button
                v-if="dossiers.length > 0"
                @click="toutEffacer"
                class="px-3.5 py-2 rounded-lg bg-danger/5 border border-danger/20 text-danger text-[11px] font-semibold cursor-pointer hover:bg-danger/10 transition-colors"
            >
                🗑 Tout Effacer
            </button>
        </div>

        <!-- ═══ Results count ═══ -->
        <div v-if="search.trim()" class="text-[11px] text-muted mb-3">
            {{ filtered.length }} dossier{{ filtered.length !== 1 ? 's' : '' }} trouvé{{ filtered.length !== 1 ? 's' : '' }}
        </div>

        <!-- ═══ Dossier list / Empty state ═══ -->
        <template v-if="filtered.length > 0">
            <div class="grid gap-3.5">
                <DossierCard
                    v-for="dossier in filtered"
                    :key="dossier.id"
                    :dossier="dossier"
                    @delete="onCardDelete"
                />
            </div>
        </template>

        <template v-else>
            <div class="text-center py-14 text-muted bg-card rounded-xl border border-dashed border-border">
                <div class="text-5xl mb-3">🗂</div>
                <div class="text-sm font-medium mb-1">Aucun dossier enregistré</div>
                <div class="text-[11px] text-muted/60">
                    Enregistrez un dossier depuis l'onglet <strong>Saisie</strong> pour le voir apparaître ici.
                </div>
            </div>
        </template>
    </div>
</template>
