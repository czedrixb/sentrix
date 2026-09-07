<script setup>
import { computed } from 'vue';

/**
 * One pagination component.
 *
 * The previous site hand-rolled the same forty-line pagination block three
 * separate times, verbatim, in shop, news and careers.
 */
const props = defineProps({
    meta: { type: Object, default: null },
});

const emit = defineEmits(['change']);

const current = computed(() => props.meta?.current_page ?? 1);
const last = computed(() => props.meta?.last_page ?? 1);

const pages = computed(() => {
    const window = [];

    for (let page = Math.max(1, current.value - 2); page <= Math.min(last.value, current.value + 2); page++) {
        window.push(page);
    }

    return window;
});

function go(page) {
    if (page >= 1 && page <= last.value && page !== current.value) {
        emit('change', page);
    }
}
</script>

<template>
    <nav v-if="last > 1" class="flex items-center justify-center gap-1 py-8" aria-label="Pagination">
        <button type="button" class="btn-ghost px-3 py-1.5" :disabled="current === 1" @click="go(current - 1)">
            Previous
        </button>

        <button v-if="pages[0] > 1" type="button" class="btn-ghost px-3 py-1.5" @click="go(1)">1</button>
        <span v-if="pages[0] > 2" class="px-1 text-ink-400">&hellip;</span>

        <button
            v-for="page in pages"
            :key="page"
            type="button"
            class="min-w-9 rounded px-3 py-1.5 font-heading text-sm font-semibold"
            :class="page === current ? 'bg-brand-600 text-white' : 'text-ink-700 hover:bg-ink-100'"
            @click="go(page)"
        >{{ page }}</button>

        <span v-if="pages.at(-1) < last - 1" class="px-1 text-ink-400">&hellip;</span>
        <button v-if="pages.at(-1) < last" type="button" class="btn-ghost px-3 py-1.5" @click="go(last)">{{ last }}</button>

        <button type="button" class="btn-ghost px-3 py-1.5" :disabled="current === last" @click="go(current + 1)">
            Next
        </button>
    </nav>
</template>
