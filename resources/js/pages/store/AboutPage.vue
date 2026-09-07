<script setup>
import { RouterLink } from 'vue-router';
import { useReferenceStore } from '@/stores/reference';

const reference = useReferenceStore();
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-16">
        <p v-reveal class="eyebrow">About Sentrix</p>
        <h1 v-reveal class="mt-3 text-4xl font-normal leading-tight text-ink-900 sm:text-5xl">
            Security equipment, specified properly and stocked locally
        </h1>

        <div v-reveal="1" class="prose-cms mt-8 text-ink-500">
            <p>
                Sentrix supplies and installs CCTV, alarm and access control systems for homes, shops, schools and
                industrial sites across the Philippines, from a network of branches that each hold their own stock.
            </p>
            <p>
                We survey before we quote. A camera in the wrong position records nothing useful, a recorder with
                too small a drive throws away the week you actually needed, and cable bought at half price is why
                so many of the systems we are called out to repair drop cameras at night. None of that appears on a
                price list, so we talk about it before you buy.
            </p>
            <p>
                Every branch carries its own inventory, so what you see in the shop is what is actually on the
                shelf at the branch you have selected &mdash; not a national figure that turns out to be
                somewhere else entirely.
            </p>
        </div>

        <div v-reveal="2" class="mt-12 grid gap-4 sm:grid-cols-3">
            <div class="card p-8 text-center">
                <p class="font-display text-4xl font-medium text-ink-900">{{ reference.pickupBranches.length }}</p>
                <p class="mt-2 text-sm text-ink-400">Branches</p>
            </div>
            <div class="card p-8 text-center">
                <p class="font-display text-4xl font-medium text-ink-900">{{ reference.brands.length }}</p>
                <p class="mt-2 text-sm text-ink-400">Brands carried</p>
            </div>
            <div class="card p-8 text-center">
                <p class="font-display text-4xl font-medium text-ink-900">{{ reference.categories.length }}</p>
                <p class="mt-2 text-sm text-ink-400">Product lines</p>
            </div>
        </div>

        <div v-reveal="3" class="mt-12 flex flex-wrap gap-3">
            <RouterLink :to="{ name: 'shop' }" class="btn-primary group">
                Browse the shop
                <span class="btn-arrow" aria-hidden="true">&rarr;</span>
            </RouterLink>
            <RouterLink :to="{ name: 'branches' }" class="btn-secondary">Find a branch</RouterLink>
        </div>
    </div>
</template>
