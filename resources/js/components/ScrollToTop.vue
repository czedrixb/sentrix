<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * A scroll-to-top button that works.
 *
 * The previous site's version called document.getElementByClass, which is not a
 * function, so it threw on load and every scroll-to-top button on the site was
 * inert.
 */
const visible = ref(false);

function onScroll() {
    visible.value = window.scrollY > 320;
}

function toTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <Transition name="fade">
        <button
            v-if="visible"
            type="button"
            class="fixed bottom-6 right-6 z-40 grid size-11 place-items-center rounded-full bg-brand-500 text-white shadow-lg hover:bg-ink-700"
            aria-label="Back to top"
            @click="toTop"
        >
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
        </button>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
