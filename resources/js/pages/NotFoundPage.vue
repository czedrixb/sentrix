<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="mx-auto max-w-lg px-4 py-24 text-center">
        <p class="font-display text-7xl text-brand-600">404</p>
        <h1 class="mt-2 font-heading text-2xl font-semibold text-ink-900">We could not find that page</h1>
        <p class="mt-2 text-sm text-ink-400">It may have moved, or the link may be out of date.</p>
        <RouterLink :to="{ name: 'home' }" class="btn-primary mt-6">Back to the shop</RouterLink>
    </div>
</template>
