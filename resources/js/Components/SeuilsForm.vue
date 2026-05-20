<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { COSTS_DEF, DEFAULT_RATIO_SEUILS } from '../lib/constants';

const emit = defineEmits(['save']);

const ratioSeuils = reactive({ ...DEFAULT_RATIO_SEUILS });

const seuilsData = reactive(
    Object.fromEntries(
        COSTS_DEF.map((c) => [
            c.id,
            {
                seuil_ok: c.seuil_ok || 0,
                seuil_warn: c.seuil_warn || 0,
                seuil_bad: c.seuil_bad || 0,
                type: c.type || 'struct',
            },
        ])
    )
);

const ratioBarZones = computed(() => {
    const ok = Number(ratioSeuils.ok) || 30;
    const warn = Number(ratioSeuils.warn) || 40;
    const bad = Number(ratioSeuils.bad) || 60;
    const maxPercent = 100;
    const okW = (ok / maxPercent) * 100;
    const warnW = ((warn - ok) / maxPercent) * 100;
    const badW = ((bad - warn) / maxPercent) * 100;
    const overW = Math.max(0, 100 - okW - warnW - badW);
    return { okW, warnW, badW, overW };
});

function applyRatioSeuils() {
    // Applied in place via v-model
}

function saveSettings() {
    emit('save', {
        seuilsData: JSON.parse(JSON.stringify(seuilsData)),
        ratioSeuils: JSON.parse(JSON.stringify(ratioSeuils)),
    });
}
</script>

<template>
    <div class="space-y-6">
        <!-- FA Ratio thresholds -->
        <section>
            <h3 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Seuils du Ratio FA (%)
            </h3>
            <p class="text-[10px] text-muted mb-3 leading-relaxed">
                Définissez les seuils pour qualifier le ratio Frais d'Approche. Un ratio FA ≤ <strong>OK</strong> est normal, 
                entre <strong>OK</strong> et <strong>WARN</strong> est en alerte, et au-delà de <strong>BAD</strong> est une anomalie.
            </p>

            <div class="flex items-center gap-3 mb-3">
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-medium" style="color: #0f9d58">OK ≤</label>
                    <input
                        v-model.number="ratioSeuils.ok"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-20 bg-white border-2 rounded-md text-text font-mono text-xs py-1.5 px-2 outline-none transition-colors text-center"
                        :style="{ borderColor: '#0f9d58' }"
                    />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-medium" style="color: #f59e0b">Warn ≤</label>
                    <input
                        v-model.number="ratioSeuils.warn"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-20 bg-white border-2 rounded-md text-text font-mono text-xs py-1.5 px-2 outline-none transition-colors text-center"
                        :style="{ borderColor: '#f59e0b' }"
                    />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] font-medium" style="color: '#d93025'">Bad &gt;</label>
                    <input
                        v-model.number="ratioSeuils.bad"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-20 bg-white border-2 rounded-md text-text font-mono text-xs py-1.5 px-2 outline-none transition-colors text-center"
                        :style="{ borderColor: '#d93025' }"
                    />
                </div>
                <button
                    @click="applyRatioSeuils"
                    class="px-4 py-1.5 rounded-md bg-navy text-white border-none text-[11px] font-semibold cursor-pointer hover:bg-navy-2 transition-colors"
                >
                    Appliquer
                </button>
            </div>

            <!-- Visual bar -->
            <div class="h-6 w-full rounded overflow-hidden flex bg-[#f0f4f9]">
                <div
                    class="h-full flex items-center justify-center text-[9px] font-bold text-white"
                    :style="{ width: ratioBarZones.okW + '%', backgroundColor: '#0f9d58' }"
                >
                    OK
                </div>
                <div
                    class="h-full flex items-center justify-center text-[9px] font-bold text-white"
                    :style="{ width: ratioBarZones.warnW + '%', backgroundColor: '#f59e0b' }"
                >
                    ALERTE
                </div>
                <div
                    class="h-full flex items-center justify-center text-[9px] font-bold text-white"
                    :style="{ width: ratioBarZones.badW + '%', backgroundColor: '#d93025' }"
                >
                    ANOMALIE
                </div>
                <div
                    v-if="ratioBarZones.overW > 0"
                    class="h-full"
                    :style="{ width: ratioBarZones.overW + '%', backgroundColor: '#374151' }"
                ></div>
            </div>
        </section>

        <!-- Per-cost thresholds -->
        <section>
            <h3 class="font-mono text-[10px] font-bold text-navy tracking-[2px] uppercase mb-3">
                Seuils par Ligne de Coût
            </h3>
            <p class="text-[10px] text-muted mb-3 leading-relaxed">
                Configurez les seuils par ligne de coût (en % du prix d'achat + fret). 
                Chaque seuil correspond au ratio du coût par rapport à la base FA.
            </p>

            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <!-- Header row -->
                <div class="grid grid-cols-[1fr_120px_70px_70px_70px] gap-2 px-4 py-2 bg-[#fafbfd] border-b border-border text-[10px] text-muted font-medium uppercase tracking-wider font-mono">
                    <span>Ligne de Coût</span>
                    <span>Type</span>
                    <span class="text-center">OK ≤</span>
                    <span class="text-center">Warn ≤</span>
                    <span class="text-center">Bad &gt;</span>
                </div>

                <div
                    v-for="cost in COSTS_DEF"
                    :key="cost.id"
                    class="grid grid-cols-[1fr_120px_70px_70px_70px] gap-2 px-4 py-2 border-b border-border/50 last:border-0 items-center hover:bg-[#fafbfd] transition-colors"
                >
                    <!-- Label -->
                    <div class="flex items-center gap-2">
                        <span
                            class="w-2.5 h-2.5 rounded-full shrink-0"
                            :style="{ backgroundColor: cost.color }"
                        ></span>
                        <span class="text-[11px] text-text truncate">{{ cost.label }}</span>
                    </div>

                    <!-- Type select -->
                    <select
                        v-model="seuilsData[cost.id].type"
                        class="bg-white border border-border rounded text-text font-mono text-[10px] py-1 px-1.5 outline-none focus:border-accent transition-colors"
                    >
                        <option value="struct">Structurel</option>
                        <option value="ineff">Inefficience</option>
                    </select>

                    <!-- OK threshold -->
                    <input
                        v-model.number="seuilsData[cost.id].seuil_ok"
                        type="number"
                        step="0.1"
                        min="0"
                        class="bg-white border-2 rounded text-text font-mono text-[10px] py-1 px-1 text-center outline-none transition-colors"
                        :style="{ borderColor: '#0f9d58' }"
                    />

                    <!-- Warn threshold -->
                    <input
                        v-model.number="seuilsData[cost.id].seuil_warn"
                        type="number"
                        step="0.1"
                        min="0"
                        class="bg-white border-2 rounded text-text font-mono text-[10px] py-1 px-1 text-center outline-none transition-colors"
                        :style="{ borderColor: '#f59e0b' }"
                    />

                    <!-- Bad threshold -->
                    <input
                        v-model.number="seuilsData[cost.id].seuil_bad"
                        type="number"
                        step="0.1"
                        min="0"
                        class="bg-white border-2 rounded text-text font-mono text-[10px] py-1 px-1 text-center outline-none transition-colors"
                        :style="{ borderColor: '#d93025' }"
                    />
                </div>
            </div>
        </section>

        <!-- Save button -->
        <div class="flex justify-end">
            <button
                @click="saveSettings"
                class="px-6 py-2.5 rounded-lg bg-gradient-to-br from-navy to-[#1e4a8a] text-white shadow font-sans text-xs font-bold cursor-pointer border-none hover:shadow-md transition-shadow"
            >
                💾 Sauvegarder les paramètres
            </button>
        </div>
    </div>
</template>
