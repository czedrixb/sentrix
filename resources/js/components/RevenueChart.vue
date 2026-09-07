<script setup>
import { computed, ref } from 'vue';
import { formatMoney } from '@/support/format';

/**
 * Paid revenue per period.
 *
 * Columns rather than a line: each point is a discrete period total, and a line
 * would imply revenue moved smoothly between two months when nothing was
 * measured in between. Zero periods stay visible as empty slots rather than
 * being dropped, so a quiet week reads as quiet instead of missing.
 *
 * Drawn with elements rather than SVG so the axis text stays at the browser's
 * own pixel grid instead of being scaled by a viewBox.
 */
const props = defineProps({
    /** @type {import('vue').PropType<Array<{key: string, label: string, full_label: string, value: string}>>} */
    points: { type: Array, default: () => [] },
    height: { type: Number, default: 200 },
});

const hovered = ref(null);

const values = computed(() => props.points.map((point) => Number(point.value) || 0));
const peak = computed(() => Math.max(...values.value, 0));

/**
 * Round the axis up to a clean number so the ticks read 0 / 50k / 100k rather
 * than 0 / 47,318 / 94,636.
 */
const ceiling = computed(() => {
    if (peak.value <= 0) {
        return 1;
    }

    const magnitude = 10 ** Math.floor(Math.log10(peak.value));
    const step = [1, 2, 2.5, 5, 10].find((multiple) => multiple * magnitude >= peak.value) ?? 10;

    return step * magnitude;
});

/** Four gridlines, top to bottom. */
const ticks = computed(() => [1, 0.75, 0.5, 0.25, 0].map((fraction) => ({
    fraction,
    value: ceiling.value * fraction,
})));

const isEmpty = computed(() => peak.value === 0);

/** The one column worth labelling directly; the rest are carried by the axis. */
const peakIndex = computed(() => (isEmpty.value ? -1 : values.value.indexOf(peak.value)));

function heightPercent(value) {
    return ceiling.value === 0 ? 0 : (value / ceiling.value) * 100;
}

/** Axis ticks are compact; the tooltip carries the exact figure. */
function compact(value) {
    if (value === 0) {
        return '0';
    }

    return new Intl.NumberFormat('en-PH', { notation: 'compact', maximumFractionDigits: 1 }).format(value);
}

const summary = computed(
    () => `Revenue per period. Highest ${formatMoney(peak.value)}`
        + `${peakIndex.value >= 0 ? ` in ${props.points[peakIndex.value].full_label}` : ''}.`,
);
</script>

<template>
    <div>
        <div class="flex gap-3">
            <!-- Y axis. Its labels are what let the reader read a column they
                 are not hovering. -->
            <div
                class="relative w-12 shrink-0 text-right text-[0.65rem] tabular-nums text-ink-400"
                :style="{ height: `${height}px` }"
                aria-hidden="true"
            >
                <span
                    v-for="tick in ticks"
                    :key="tick.fraction"
                    class="absolute right-0 -translate-y-1/2"
                    :style="{ top: `${(1 - tick.fraction) * 100}%` }"
                >{{ compact(tick.value) }}</span>
            </div>

            <div class="relative min-w-0 flex-1">
                <!-- Gridlines: hairline, solid, one step off the surface. -->
                <div class="absolute inset-0" :style="{ height: `${height}px` }" aria-hidden="true">
                    <div
                        v-for="tick in ticks"
                        :key="tick.fraction"
                        class="absolute inset-x-0 border-t border-ink-100"
                        :style="{ top: `${(1 - tick.fraction) * 100}%` }"
                    />
                </div>

                <!-- Columns. gap-0.5 is the 2px of surface that separates
                     neighbours; no stroke is drawn around a bar. -->
                <div
                    class="relative flex items-end gap-0.5"
                    :style="{ height: `${height}px` }"
                    role="img"
                    :aria-label="summary"
                >
                    <div
                        v-for="(point, index) in points"
                        :key="point.key"
                        class="group relative flex h-full flex-1 cursor-default items-end justify-center"
                        @mouseenter="hovered = index"
                        @mouseleave="hovered = null"
                        @focusin="hovered = index"
                        @focusout="hovered = null"
                    >
                        <!-- Full-height hit target, so a near-zero column is
                             still hoverable. -->
                        <button
                            type="button"
                            class="absolute inset-0 focus:outline-none"
                            :aria-label="`${point.full_label}: ${formatMoney(point.value)}`"
                        />

                        <div
                            class="w-full max-w-6 rounded-t transition-colors"
                            :class="hovered === index ? 'bg-brand-700' : 'bg-brand-600'"
                            :style="{ height: `${Math.max(heightPercent(Number(point.value) || 0), Number(point.value) > 0 ? 2 : 0)}%` }"
                        />

                        <!-- Only the peak is labelled; a number on every column
                             goes unread. -->
                        <span
                            v-if="index === peakIndex && hovered === null"
                            class="pointer-events-none absolute bottom-full mb-1 whitespace-nowrap text-[0.65rem] font-semibold tabular-nums text-ink-400"
                        >{{ compact(peak) }}</span>

                        <div
                            v-if="hovered === index"
                            class="pointer-events-none absolute bottom-full z-10 mb-2 whitespace-nowrap rounded bg-ink-900 px-2 py-1 text-[0.7rem] text-white shadow-lg"
                        >
                            <span class="block font-semibold">{{ formatMoney(point.value) }}</span>
                            <span class="block text-ink-400">{{ point.full_label }}</span>
                        </div>
                    </div>
                </div>

                <p
                    v-if="isEmpty"
                    class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-xs text-ink-400"
                >
                    No paid revenue in this period
                </p>

                <!-- X axis. Every other tick past twelve columns, so thirty days
                     of labels do not collide. -->
                <div class="mt-2 flex gap-0.5 text-[0.65rem] text-ink-400" aria-hidden="true">
                    <span
                        v-for="(point, index) in points"
                        :key="point.key"
                        class="min-w-0 flex-1 truncate text-center"
                    >{{ points.length > 12 && index % 3 !== 0 ? '' : point.label }}</span>
                </div>
            </div>
        </div>

        <!-- The same figures as text, for anyone who cannot use the plot. -->
        <table class="sr-only">
            <caption>Revenue per period</caption>
            <thead>
                <tr><th scope="col">Period</th><th scope="col">Revenue</th></tr>
            </thead>
            <tbody>
                <tr v-for="point in points" :key="point.key">
                    <th scope="row">{{ point.full_label }}</th>
                    <td>{{ formatMoney(point.value) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
