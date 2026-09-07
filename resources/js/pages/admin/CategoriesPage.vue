<script setup>
import { computed, onMounted } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useResourceCrud } from '@/support/useResourceCrud';

/**
 * Category management.
 *
 * The previous system shipped categories as a frozen set: create and store were
 * empty stubs and the action column was commented out of the view, so the four
 * hardcoded names could never change. Here the tree is data, and the storefront
 * filters follow it without a deployment.
 */
const auth = useAuthStore();

const crud = useResourceCrud({
    // `flat` returns children alongside parents, which is what a management list
    // needs; the storefront asks for the nested shape instead.
    list: () => admin.categories({ flat: 1 }),
    create: (payload) => admin.createCategory(payload),
    update: (slug, payload) => admin.updateCategory(slug, payload),
    remove: (slug) => admin.deleteCategory(slug),
    keyOf: (category) => category.slug,
    blank: { name: '', slug: '', parent_id: null, description: '', position: 0 },
    toForm: (category) => ({
        name: category.name,
        slug: category.slug,
        parent_id: category.parent_id,
        description: category.description ?? '',
        position: category.position ?? 0,
    }),
    savedMessage: (category, created) => created
        ? `${category.name} created and is already filterable in the shop.`
        : `${category.name} updated.`,
});

const { items: categories, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('categories.manage');

/** Everything except the category being edited: nothing can parent itself. */
const parentOptions = computed(() =>
    categories.value.filter((candidate) => candidate.id !== editing.value?.id),
);

function parentName(id) {
    return categories.value.find((candidate) => candidate.id === id)?.name ?? '—';
}

onMounted(crud.load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Categories</h1>
                <p class="text-sm text-ink-400">
                    Categories are rows, not code &mdash; add one and the shop filters pick it up.
                </p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Add category
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-2xl bg-white" />
        </div>

        <div v-else class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Parent</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="category in categories" :key="category.id">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ink-900">{{ category.name }}</p>
                            <p class="text-xs text-ink-400">/{{ category.slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ parentName(category.parent_id) }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ category.position }}</td>
                        <td class="px-4 py-3 text-right">
                            <template v-if="canManage()">
                                <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(category)">
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                                    @click="crud.remove(category)"
                                >
                                    Remove
                                </button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-ink-400">No categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-lg overflow-auto p-6" @submit.prevent="crud.save()">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.name}` : 'New category' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="category-name">Name</label>
                        <input id="category-name" v-model="form.name" class="field-input">
                        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="category-slug">Slug</label>
                        <input id="category-slug" v-model="form.slug" class="field-input" placeholder="Derived from the name">
                        <span v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="category-parent">Parent</label>
                        <select id="category-parent" v-model="form.parent_id" class="field-input">
                            <option :value="null">Top level</option>
                            <option v-for="option in parentOptions" :key="option.id" :value="option.id">
                                {{ option.name }}
                            </option>
                        </select>
                        <span v-if="errors.parent_id" class="field-error">{{ errors.parent_id[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="category-description">Description</label>
                        <textarea id="category-description" v-model="form.description" rows="3" class="field-input" />
                    </div>

                    <div>
                        <label class="field-label" for="category-position">Position</label>
                        <input id="category-position" v-model.number="form.position" type="number" min="0" class="field-input">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
