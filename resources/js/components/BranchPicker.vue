<script setup>
import { computed } from 'vue';
import { useReferenceStore } from '@/stores/reference';
import { useCartStore } from '@/stores/cart';

/**
 * Branch selector.
 *
 * The options come from the API, so a branch created in the admin appears here
 * on the next page load with nothing to deploy. While a cart holds items the
 * picker is locked to that cart's branch, because an order cannot mix branches.
 */
const reference = useReferenceStore();
const cart = useCartStore();

const lockedBranch = computed(() => (cart.isEmpty ? null : cart.branch));

const value = computed({
    get: () => lockedBranch.value?.slug ?? reference.selectedBranchSlug,
    set: (slug) => reference.selectBranch(slug),
});
</script>

<template>
    <label class="flex items-center gap-2">
        <span class="sr-only">Branch</span>
        <select
            v-model="value"
            :disabled="lockedBranch !== null"
            class="rounded border border-ink-200 px-2 py-1.5 text-sm disabled:bg-ink-100 disabled:text-ink-400"
            :title="lockedBranch ? 'Your cart is tied to this branch' : 'Choose a branch'"
        >
            <option v-for="branch in reference.pickupBranches" :key="branch.id" :value="branch.slug">
                {{ branch.name }}
            </option>
        </select>
    </label>
</template>
