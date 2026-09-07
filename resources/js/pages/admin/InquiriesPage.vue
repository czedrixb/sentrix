<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatDateTime } from '@/support/format';

/**
 * One enquiry screen for every branch.
 *
 * The previous admin had eight of these, each a copy of the last.
 */
const auth = useAuthStore();
const reference = useReferenceStore();

const inquiries = ref([]);
const meta = ref(null);
const loading = ref(true);
const filters = reactive({ q: '', branch_id: '', unhandled_only: false, page: 1 });

async function load() {
    loading.value = true;

    try {
        const response = await admin.inquiries({
            q: filters.q || undefined,
            branch_id: filters.branch_id || undefined,
            unhandled_only: filters.unhandled_only ? 1 : undefined,
            page: filters.page,
        });

        inquiries.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function toggle(inquiry) {
    Object.assign(inquiry, await admin.toggleInquiryHandled(inquiry.id));
}

watch(() => [filters.branch_id, filters.unhandled_only], () => {
    filters.page = 1;
    load();
});

watch(() => filters.page, load);

onMounted(load);
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Enquiries</h1>
            <p class="text-sm text-ink-400">
                {{ auth.branchName ? `${auth.branchName} and general enquiries` : 'All branches' }}
            </p>
        </header>

        <div class="card mb-4 flex flex-wrap items-end gap-3 p-4">
            <form class="flex gap-2" @submit.prevent="filters.page = 1; load()">
                <input v-model="filters.q" type="search" placeholder="Name, email or message" class="field-input w-64">
                <button type="submit" class="btn-primary px-4">Search</button>
            </form>

            <label v-if="!auth.branchId" class="text-sm">
                <span class="field-label">Branch</span>
                <select v-model="filters.branch_id" class="field-input">
                    <option value="">All</option>
                    <option v-for="branch in reference.branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                </select>
            </label>

            <label class="flex items-center gap-2 pb-2 text-sm">
                <input v-model="filters.unhandled_only" type="checkbox" class="rounded">
                Waiting only
            </label>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-24 animate-pulse rounded-2xl bg-white" />
        </div>

        <p v-else-if="inquiries.length === 0" class="card p-12 text-center text-ink-400">No enquiries.</p>

        <div v-else class="space-y-3">
            <article v-for="inquiry in inquiries" :key="inquiry.id" class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-heading font-semibold text-ink-900">{{ inquiry.name }}</p>
                        <p class="text-xs text-ink-400">
                            {{ inquiry.email }} &middot; +63 {{ inquiry.phone }} &middot;
                            {{ inquiry.branch?.name ?? 'General enquiry' }} &middot;
                            {{ formatDateTime(inquiry.created_at) }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="inquiry.handled_at ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                        @click="toggle(inquiry)"
                    >
                        {{ inquiry.handled_at ? 'Handled' : 'Mark handled' }}
                    </button>
                </div>

                <p class="mt-3 whitespace-pre-line text-sm text-ink-500">{{ inquiry.message }}</p>
            </article>
        </div>

        <PaginationBar :meta="meta" @change="filters.page = $event" />
    </div>
</template>
