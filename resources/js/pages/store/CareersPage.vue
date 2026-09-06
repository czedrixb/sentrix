<script setup>
import { onMounted, ref, watch } from 'vue';
import { content } from '@/api';
import PaginationBar from '@/components/PaginationBar.vue';

const careers = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(true);

async function load() {
    loading.value = true;

    try {
        const response = await content.careers({ page: page.value });
        careers.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

watch(page, load);
onMounted(load);
</script>

<template>
    <div class="mx-auto max-w-4xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-700">Careers</h1>
        <p class="mt-1 text-sm text-slate-500">Open roles across our branches.</p>

        <div v-if="loading" class="mt-8 space-y-4">
            <div v-for="n in 3" :key="n" class="h-32 animate-pulse rounded-lg bg-slate-100" />
        </div>

        <p v-else-if="careers.length === 0" class="card mt-8 p-12 text-center text-slate-500">
            No open roles right now. Do check back.
        </p>

        <div v-else class="mt-8 space-y-4">
            <article v-for="career in careers" :key="career.id" class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-heading text-lg font-bold text-ink-700">{{ career.title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            <span v-if="career.branch">{{ career.branch.name }} &middot; </span>
                            <span v-if="career.employment_type">{{ career.employment_type }} &middot; </span>
                            {{ career.vacancies }} opening{{ career.vacancies === 1 ? '' : 's' }}
                        </p>
                    </div>

                    <a v-if="career.apply_email" :href="`mailto:${career.apply_email}?subject=Application: ${career.title}`" class="btn-primary">
                        Apply now
                    </a>
                </div>

                <div v-if="career.description" class="prose-cms mt-4 text-sm text-slate-600" v-html="career.description" />
            </article>
        </div>

        <PaginationBar :meta="meta" @change="page = $event" />
    </div>
</template>
