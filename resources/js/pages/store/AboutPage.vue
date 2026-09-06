<script setup>
import { RouterLink } from 'vue-router';
import { useReferenceStore } from '@/stores/reference';

const reference = useReferenceStore();
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-12">
        <h1 class="font-display text-4xl text-ink-700">About Kompra</h1>

        <div class="prose-cms mt-6 text-slate-600">
            <p>
                Kompra supplies equipment, consumables, spare parts and accessories to businesses across the
                Philippines, from a network of branches that each hold their own stock.
            </p>
            <p>
                Every branch carries its own inventory, so what you see in the shop is what is actually on the
                shelf at the branch you have selected &mdash; not a national figure that turns out to be
                somewhere else entirely.
            </p>
        </div>

        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div class="card p-6 text-center">
                <p class="font-display text-4xl text-brand-500">{{ reference.pickupBranches.length }}</p>
                <p class="mt-1 text-sm text-slate-500">Branches</p>
            </div>
            <div class="card p-6 text-center">
                <p class="font-display text-4xl text-brand-500">{{ reference.brands.length }}</p>
                <p class="mt-1 text-sm text-slate-500">Brands carried</p>
            </div>
            <div class="card p-6 text-center">
                <p class="font-display text-4xl text-brand-500">{{ reference.categories.length }}</p>
                <p class="mt-1 text-sm text-slate-500">Product lines</p>
            </div>
        </div>

        <div class="mt-10 flex gap-3">
            <RouterLink :to="{ name: 'shop' }" class="btn-primary">Browse the shop</RouterLink>
            <RouterLink :to="{ name: 'branches' }" class="btn-secondary">Find a branch</RouterLink>
        </div>
    </div>
</template>
