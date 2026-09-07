<script setup>
import { ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { content } from '@/api';
import { formatDate } from '@/support/format';

const route = useRoute();
const router = useRouter();

const post = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;

    try {
        post.value = await content.post(route.params.slug);
    } catch {
        router.replace({ name: 'not-found' });
    } finally {
        loading.value = false;
    }
}

watch(() => route.params.slug, load, { immediate: true });
</script>

<template>
    <article class="mx-auto max-w-3xl px-4 py-10">
        <div v-if="loading" class="h-96 animate-pulse rounded-2xl bg-ink-100" />

        <template v-else-if="post">
            <RouterLink :to="{ name: 'news' }" class="text-sm text-ink-400 hover:text-brand-600">&larr; All news</RouterLink>

            <h1 class="mt-3 font-heading text-3xl font-semibold text-ink-900">{{ post.title }}</h1>
            <p class="mt-1 text-sm text-ink-400">{{ formatDate(post.published_at) }} &middot; {{ post.author }}</p>

            <!-- Sanitised on write; rendered here as stored. -->
            <div v-if="post.video_url" class="prose-cms mt-6">
                <iframe :src="post.video_url" title="Video" allowfullscreen class="aspect-video w-full rounded" />
            </div>
            <img v-else-if="post.thumbnail_url" :src="post.thumbnail_url" :alt="post.title" class="mt-6 w-full rounded-2xl">

            <div class="prose-cms mt-6 text-ink-500" v-html="post.body" />

            <div v-if="post.images?.length" class="mt-8 grid gap-3 sm:grid-cols-3">
                <img v-for="image in post.images" :key="image.id" :src="image.url" :alt="image.caption ?? ''" class="rounded">
            </div>
        </template>
    </article>
</template>
