<script setup>
import { onMounted, ref } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import { useResourceCrud } from '@/support/useResourceCrud';
import PaginationBar from '@/components/PaginationBar.vue';

/**
 * Vacancies.
 *
 * A vacancy points at a branch by id rather than naming a city, so a new branch
 * can advertise the day it is created.
 */
const auth = useAuthStore();
const reference = useReferenceStore();

const page = ref(1);
const meta = ref(null);

const crud = useResourceCrud({
    list: async () => {
        const response = await admin.careers({ page: page.value });

        meta.value = response.meta;

        return response.data;
    },
    create: (payload) => admin.createCareer(payload),
    update: (slug, payload) => admin.updateCareer(slug, payload),
    remove: (slug) => admin.deleteCareer(slug),
    keyOf: (career) => career.slug,
    blank: {
        title: '', slug: '', branch_id: null, vacancies: 1,
        employment_type: 'Full-time', description: '', apply_email: '', is_open: true,
    },
    toForm: (career) => ({
        title: career.title,
        slug: career.slug,
        branch_id: career.branch?.id ?? null,
        vacancies: career.vacancies ?? 1,
        employment_type: career.employment_type ?? '',
        description: career.description ?? '',
        apply_email: career.apply_email ?? '',
        is_open: career.is_open,
    }),
    savedMessage: (career, created) => `${career.title} ${created ? 'posted' : 'updated'}.`,
});

const { items: careers, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('careers.manage');

async function goToPage(next) {
    page.value = next;
    await crud.load();
}

onMounted(async () => {
    await Promise.all([crud.load(), reference.load()]);
});
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Careers</h1>
                <p class="text-sm text-ink-400">Vacancies listed on the storefront careers page.</p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Post vacancy
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-2xl bg-white" />
        </div>

        <template v-else>
            <div class="card overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                        <tr>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Branch</th>
                            <th class="px-4 py-3">Vacancies</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        <tr v-for="career in careers" :key="career.id">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-ink-900">{{ career.title }}</p>
                                <p class="text-xs text-ink-400">{{ career.employment_type ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-ink-500">{{ career.branch?.name ?? 'Any branch' }}</td>
                            <td class="px-4 py-3 text-ink-500">{{ career.vacancies }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="career.is_open ? 'bg-emerald-50 text-emerald-700' : 'bg-ink-100 text-ink-400'"
                                >{{ career.is_open ? 'Open' : 'Closed' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <template v-if="canManage()">
                                    <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(career)">
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                                        @click="crud.remove(career)"
                                    >
                                        Delete
                                    </button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="careers.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-ink-400">No vacancies posted.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar v-if="meta" class="mt-4" :meta="meta" @change="goToPage" />
        </template>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-xl overflow-auto p-6" @submit.prevent="crud.save()">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.title}` : 'New vacancy' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="career-title">Title</label>
                        <input id="career-title" v-model="form.title" class="field-input">
                        <span v-if="errors.title" class="field-error">{{ errors.title[0] }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="career-branch">Branch</label>
                            <select id="career-branch" v-model="form.branch_id" class="field-input">
                                <option :value="null">Any branch</option>
                                <option v-for="branch in reference.branches" :key="branch.id" :value="branch.id">
                                    {{ branch.name }}
                                </option>
                            </select>
                            <span v-if="errors.branch_id" class="field-error">{{ errors.branch_id[0] }}</span>
                        </div>
                        <div>
                            <label class="field-label" for="career-vacancies">Vacancies</label>
                            <input id="career-vacancies" v-model.number="form.vacancies" type="number" min="1" class="field-input">
                            <span v-if="errors.vacancies" class="field-error">{{ errors.vacancies[0] }}</span>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="career-type">Employment type</label>
                            <input id="career-type" v-model="form.employment_type" class="field-input">
                        </div>
                        <div>
                            <label class="field-label" for="career-email">Applications to</label>
                            <input id="career-email" v-model="form.apply_email" type="email" class="field-input">
                            <span v-if="errors.apply_email" class="field-error">{{ errors.apply_email[0] }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="career-description">Description</label>
                        <textarea id="career-description" v-model="form.description" rows="6" class="field-input" />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_open" type="checkbox" class="rounded">
                        Accepting applications
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save vacancy' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
