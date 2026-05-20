<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close']);

function close() {
    emit('close');
}

function onOverlayClick(e) {
    if (e.target === e.currentTarget) {
        close();
    }
}

watch(() => props.show, (val) => {
    if (val) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="fixed inset-0 z-[200] bg-black/50 flex items-center justify-center p-4"
                @click="onOverlayClick"
            >
                <Transition name="modal-scale" appear>
                    <div
                        v-if="show"
                        class="bg-card border border-border rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
                    >
                        <div class="flex items-center justify-between px-5 py-3.5 border-b border-border">
                            <h3 class="font-mono text-xs font-bold text-navy tracking-wide uppercase">
                                {{ title }}
                            </h3>
                            <button
                                @click="close"
                                class="text-muted hover:text-text text-lg leading-none cursor-pointer bg-transparent border-none p-0 w-6 h-6 flex items-center justify-center rounded transition-colors"
                            >
                                ×
                            </button>
                        </div>
                        <div class="p-5">
                            <slot name="body" />
                        </div>
                        <div v-if="$slots.actions" class="flex justify-end gap-2 px-5 py-3 border-t border-border bg-[#fafbfd]">
                            <slot name="actions" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active {
    transition: transform 0.25s ease, opacity 0.25s ease;
}
.modal-scale-leave-active {
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.modal-scale-enter-from {
    transform: scale(0.95);
    opacity: 0;
}
.modal-scale-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>
