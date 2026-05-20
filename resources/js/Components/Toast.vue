<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    message: { type: String, required: true },
    type: { type: String, default: 'ok' },
});

const visible = ref(false);
const emit = defineEmits(['dismiss']);

const icons = {
    ok: '✅',
    warn: '⚠️',
    danger: '❌',
};

const colors = {
    ok: { border: '#0f9d58', bg: '#f0fdf4', text: '#166534' },
    warn: { border: '#f59e0b', bg: '#fffbeb', text: '#92400e' },
    danger: { border: '#d93025', bg: '#fef2f2', text: '#991b1b' },
};

onMounted(() => {
    requestAnimationFrame(() => {
        visible.value = true;
    });
    setTimeout(() => {
        visible.value = false;
        setTimeout(() => emit('dismiss'), 300);
    }, 3000);
});
</script>

<template>
    <Transition name="toast-slide">
        <div
            v-if="visible"
            class="fixed bottom-6 right-6 z-[300] px-4 py-3 rounded-lg shadow-lg border-l-4 max-w-sm"
            :style="{
                backgroundColor: colors[type]?.bg || colors.ok.bg,
                borderColor: colors[type]?.border || colors.ok.border,
                color: colors[type]?.text || colors.ok.text,
            }"
        >
            <div class="flex items-center gap-2">
                <span class="text-sm shrink-0">{{ icons[type] || icons.ok }}</span>
                <span class="text-xs font-medium">{{ message }}</span>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.toast-slide-enter-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.toast-slide-leave-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.toast-slide-enter-from {
    transform: translateY(20px);
    opacity: 0;
}
.toast-slide-leave-to {
    transform: translateY(20px);
    opacity: 0;
}
</style>
