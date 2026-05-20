<script setup>
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const tabs = [
    { route: 'saisie',       match: 'saisie',       label: 'Saisie' },
    { route: 'analyse',      match: 'analyse',      label: '📊 Analyse' },
    { route: 'dossiers.index', match: 'dossiers',   label: '🗂 Dossiers' },
    { route: 'statistiques', match: 'statistiques', label: '📈 Statistiques' },
    { route: 'produits',     match: 'produits',     label: '📦 Par Produit' },
    { route: 'fournisseurs', match: 'fournisseurs', label: '🏭 Par Fournisseur' },
    { route: 'parametres',   match: 'parametres',   label: '⚙️ Paramètres' },
    { route: 'export',       match: 'export',       label: '📥 Export CSV' },
];

const page = usePage();

function switchTab(tab) { router.get(route(tab.route)); }
function isActive(tab) { return page.component && page.component.toLowerCase().includes(tab.match); }

const userProfile = ref(null);

onMounted(() => {
    try {
        const p = localStorage.getItem('batifer_user_profile');
        if (p) userProfile.value = JSON.parse(p);
    } catch (e) { userProfile.value = null; }
});

function showProfileSetup() {
    const name = prompt("Entrez votre prénom ou identifiant :");
    if (!name?.trim()) return;
    const id = name.trim().toLowerCase().replace(/\s+/g, '_') + '_' + Date.now().toString(36).slice(-4);
    userProfile.value = { id, name: name.trim(), createdAt: new Date().toISOString() };
    localStorage.setItem('batifer_user_profile', JSON.stringify(userProfile.value));
    router.reload();
}

function forgetProfile() {
    if (!confirm("Supprimer votre profil et vos données ?")) return;
    localStorage.removeItem('batifer_user_profile');
    userProfile.value = null;
    router.reload();
}
</script>

<template>
    <div class="flex flex-col h-screen">
        <header class="bg-gradient-to-br from-navy to-[#1e4a8a] border-b-[3px] border-accent px-6 py-3.5 flex items-center justify-between sticky top-0 z-[100] shadow-[0_2px_12px_rgba(22,49,107,0.2)]">
            <div>
                <div class="font-mono text-base font-bold text-white">BETTY<span class="text-accent-2 font-normal"> </span>MOHAMED</div>
                <span class="text-[10px] text-white/60 block mt-px tracking-wider">ENCG.BM PFE</span>
            </div>
            <div class="flex gap-0.5 bg-white/15 rounded-[10px] p-0.5 border border-white/25">
                <button v-for="tab in tabs" :key="tab.route" @click="switchTab(tab)"
                    class="px-4 py-1.5 rounded-md border-none bg-transparent text-white/70 font-sans text-xs font-medium cursor-pointer transition-all duration-200"
                    :class="tab.route === 'export' ? '!bg-[rgba(0,200,159,0.2)] !text-white !border !border-[rgba(0,200,159,0.4)]' : ''"
                    :style="isActive(tab) ? 'background:rgba(255,255,255,0.25);color:#fff;font-weight:700' : ''">
                    {{ tab.label }}
                </button>
            </div>
        </header>
        <div class="flex items-center justify-between px-6 py-2 bg-white border-b border-border shadow-[0_1px_4px_rgba(22,49,107,0.06)]">
            <template v-if="userProfile">
                <span class="flex items-center gap-2"><span class="bg-navy text-white rounded-full w-7 h-7 flex items-center justify-center font-bold text-xs shrink-0">{{ userProfile.name.charAt(0).toUpperCase() }}</span><span class="text-xs text-text">Session : <strong>{{ userProfile.name }}</strong></span></span>
                <button @click="forgetProfile" class="bg-[#fee2e2] border border-[#fca5a5] rounded-md px-3 py-1 text-[11px] cursor-pointer text-[#b91c1c] font-semibold">🗑 Effacer mes données</button>
            </template>
            <template v-else>
                <span class="text-xs text-muted">💡 Identifiez-vous pour personnaliser la session.</span>
                <button @click="showProfileSetup" class="bg-navy text-white border-none rounded-md px-3.5 py-1.5 text-xs font-semibold cursor-pointer">👤 Mémoriser mes données</button>
            </template>
        </div>
        <div class="flex flex-1 overflow-hidden"><slot /></div>
    </div>
</template>
