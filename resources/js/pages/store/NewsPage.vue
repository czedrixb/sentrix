<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { content } from '@/api';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatDate } from '@/support/format';

const posts = ref([]);
const meta = ref(null);
const page = ref(1);
const search = ref('');
const loading = ref(true);

async function load() {
    loading.value = true;

    try {
        const response = await content.posts({ page: page.value, q: search.value || undefined });
        posts.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

function submitSearch() {
    page.value = 1;
    load();
}

watch(page, load);
onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="font-display text-4xl text-ink-900">News</h1>

            <form class="flex gap-2" @submit.prevent="submitSearch">
                <input v-model="search" type="search" placeholder="Search news" class="field-input">
                <button type="submit" class="btn-primary px-4">Search</button>
            </form>
        </div>

        <div v-if="loading" class="mt-8 grid gap-6 md:grid-cols-3">
            <div v-for="n in 6" :key="n" class="h-72 animate-pulse rounded-2xl bg-ink-100" />
        </div>

        <p v-else-if="posts.length === 0" class="card mt-8 p-12 text-center text-ink-400">
            No articles yet.
        </p>

        <div v-else class="mt-8 grid gap-6 md:grid-cols-3">
            <RouterLink
                v-for="post in posts"
                :key="post.id"
                :to="{ name: 'post', params: { slug: post.slug } }"
                class="card overflow-hidden transition duration-300 ease-soft hover:shadow-md"
            >
                <div class="aspect-video bg-ink-100">
                    <img v-if="post.thumbnail_url" :src="post.thumbnail_url" :alt="post.title" class="size-full object-cover">
                </div>
                <div class="p-4">
                    <p class="text-xs text-ink-400">{{ formatDate(post.published_at) }} &middot; {{ post.author }}</p>
                    <p class="mt-1 font-heading font-semibold text-ink-900">{{ post.title }}</p>
                    <p class="mt-2 line-clamp-3 text-sm text-ink-500">{{ post.excerpt }}</p>
                </div>
            </RouterLink>
        </div>

        <PaginationBar :meta="meta" @change="page = $event" />
    </div>
</template>
