<script setup>
import { onMounted } from 'vue';
import { admin, toFormData } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useResourceCrud } from '@/support/useResourceCrud';

/**
 * Brand management.
 *
 * Brands were a commented-out seeder in the previous system and appeared in the
 * shop only as a hardcoded list of names. They are rows now, with an optional
 * logo stored on the public disk.
 */
const auth = useAuthStore();

const crud = useResourceCrud({
    list: () => admin.brands(),
    create: (payload) => admin.createBrand(payload),
    update: (slug, payload) => admin.updateBrand(slug, payload),
    remove: (slug) => admin.deleteBrand(slug),
    keyOf: (brand) => brand.slug,
    blank: { name: '', slug: '', position: 0, logo: null },
    toForm: (brand) => ({ name: brand.name, slug: brand.slug, logo: null }),
    savedMessage: (brand, created) => created
        ? `${brand.name} created and is now a shop filter.`
        : `${brand.name} updated.`,
});

const { items: brands, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('brands.manage');

function chooseLogo(event) {
    form.logo = event.target.files?.[0] ?? null;
}

/**
 * A logo makes this multipart; without one a plain object is enough, and the
 * server treats the file as optional either way.
 */
function submit() {
    return crud.save(form.logo ? toFormData({ ...form }) : { ...form, logo: undefined });
}

onMounted(crud.load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-bold text-ink-700">Brands</h1>
                <p class="text-sm text-slate-500">Every brand the catalogue can be filtered by.</p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Add brand
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="n in 6" :key="n" class="h-24 animate-pulse rounded-lg bg-white" />
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="brand in brands" :key="brand.id" class="card flex items-center gap-4 p-4">
                <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded bg-slate-100">
                    <img v-if="brand.logo_url" :src="brand.logo_url" :alt="brand.name" class="size-full object-contain">
                    <span v-else class="font-display text-xl text-slate-400">{{ brand.name.charAt(0) }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-ink-700">{{ brand.name }}</p>
                    <p class="truncate text-xs text-slate-500">/{{ brand.slug }}</p>
                </div>

                <div v-if="canManage()" class="flex shrink-0 flex-col items-end">
                    <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(brand)">Edit</button>
                    <button
                        type="button"
                        class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                        @click="crud.remove(brand)"
                    >
                        Remove
                    </button>
                </div>
            </article>

            <p v-if="brands.length === 0" class="col-span-full py-8 text-center text-slate-400">No brands yet.</p>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-md overflow-auto p-6" @submit.prevent="submit">
                <h2 class="font-heading text-lg font-bold text-ink-700">
                    {{ editing ? `Edit ${editing.name}` : 'New brand' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="brand-name">Name</label>
                        <input id="brand-name" v-model="form.name" class="field-input">
                        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="brand-slug">Slug</label>
                        <input id="brand-slug" v-model="form.slug" class="field-input" placeholder="Derived from the name">
                        <span v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="brand-logo">Logo</label>
                        <input
                            id="brand-logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/svg+xml"
                            class="field-input"
                            @change="chooseLogo"
                        >
                        <span v-if="errors.logo" class="field-error">{{ errors.logo[0] }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save brand' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
