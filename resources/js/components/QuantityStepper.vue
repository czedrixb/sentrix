<script setup>
import { computed } from 'vue';

/**
 * A quantity input clamped to its bounds.
 *
 * The previous site's stepper bound to a hardcoded element id at the top of the
 * global layout script, so on every page that was not the product page it threw
 * a TypeError and killed every handler registered after it.
 */
const props = defineProps({
    modelValue: { type: Number, required: true },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 999 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const canDecrease = computed(() => !props.disabled && props.modelValue > props.min);
const canIncrease = computed(() => !props.disabled && props.modelValue < props.max);

function clamp(value) {
    const parsed = Number.parseInt(value, 10);

    if (!Number.isFinite(parsed)) {
        return props.min;
    }

    return Math.min(props.max, Math.max(props.min, parsed));
}

function set(value) {
    emit('update:modelValue', clamp(value));
}
</script>

<template>
    <div class="inline-flex items-stretch rounded border border-ink-200">
        <button
            type="button"
            class="px-3 text-lg leading-none text-ink-700 disabled:opacity-40"
            :disabled="!canDecrease"
            aria-label="Decrease quantity"
            @click="set(modelValue - 1)"
        >&minus;</button>

        <input
            :value="modelValue"
            type="number"
            inputmode="numeric"
            :min="min"
            :max="max"
            :disabled="disabled"
            class="w-14 border-x border-ink-200 py-2 text-center text-sm focus:outline-none"
            @change="set($event.target.value)"
        >

        <button
            type="button"
            class="px-3 text-lg leading-none text-ink-700 disabled:opacity-40"
            :disabled="!canIncrease"
            aria-label="Increase quantity"
            @click="set(modelValue + 1)"
        >+</button>
    </div>
</template>
