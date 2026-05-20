<script setup>
import Sidebar from '../Components/Sidebar.vue';
import { router } from '@inertiajs/vue3';

// The Sidebar handles save/analyse internally via its own handlers.
// These event handlers exist for the conceptual API where Sidebar
// would emit events instead of directly calling router.
function handleSave(formData) {
    router.post('/dossiers', formData, {
        onSuccess: () => { /* toast handled by server flash */ },
    });
}

function handleAnalyse(formData) {
    router.get('/analyse', { data: formData });
}
</script>

<template>
    <Sidebar @save="handleSave" @analyse="handleAnalyse" />
    <div class="flex-1 bg-bg p-5">
        <div class="text-center py-10 text-muted text-sm bg-[#f8fafc] rounded-[10px] border border-dashed border-border">
            <div class="text-4xl mb-3">📋</div>
            <div>Saisissez les informations du dossier<br>puis <strong>Enregistrer</strong> ou <strong>Analyser</strong></div>
        </div>
    </div>
</template>
