<script setup>
import { onMounted, ref } from 'vue';
import { reference as referenceApi } from '@/api';

const images = ref([]);
const lightbox = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        images.value = await referenceApi.gallery();
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-900">Gallery</h1>

        <div v-if="loading" class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="n in 8" :key="n" class="aspect-square animate-pulse rounded-2xl bg-ink-100" />
        </div>

        <p v-else-if="images.length === 0" class="card mt-8 p-12 text-center text-ink-400">
            There are no photos yet.
        </p>

        <div v-else class="mt-8 grid gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <button
                v-for="image in images"
                :key="image.id"
                type="button"
                class="aspect-square overflow-hidden rounded-2xl bg-ink-100"
                @click="lightbox = image"
            >
                <img :src="image.url" :alt="image.caption ?? ''" loading="lazy" class="size-full object-cover transition duration-300 ease-soft hover:scale-105">
            </button>
        </div>

        <!-- A small lightbox, rather than pulling in a library for one page. -->
        <div
            v-if="lightbox"
            class="fixed inset-0 z-50 grid place-items-center bg-black/80 p-6"
            role="dialog"
            @click="lightbox = null"
            @keydown.esc="lightbox = null"
        >
            <figure class="max-h-full max-w-4xl">
                <img :src="lightbox.url" :alt="lightbox.caption ?? ''" class="max-h-[80vh] rounded-2xl object-contain">
                <figcaption v-if="lightbox.caption" class="mt-3 text-center text-sm text-white">{{ lightbox.caption }}</figcaption>
            </figure>
        </div>
    </div>
</template>
