<script setup>
import { onMounted } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useResourceCrud } from '@/support/useResourceCrud';

/**
 * Homepage banners.
 *
 * An image is required on create and optional on update, so the existing file
 * survives an edit that only changes the headline.
 */
const auth = useAuthStore();

const crud = useResourceCrud({
    list: () => admin.banners(),
    create: (payload) => admin.createBanner(payload),
    update: (id, payload) => admin.updateBanner(id, payload),
    remove: (id) => admin.deleteBanner(id),
    blank: { headline: '', link_url: '', position: 0, is_active: true, image: null },
    toForm: (banner) => ({
        headline: banner.headline ?? '',
        link_url: banner.link_url ?? '',
        position: banner.position ?? 0,
        is_active: true,
        image: null,
    }),
    savedMessage: (banner, created) => created ? 'Banner added.' : 'Banner updated.',
});

const { items: banners, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('content.manage');

function chooseImage(event) {
    form.image = event.target.files?.[0] ?? null;
}

/**
 * Both endpoints are multipart, so the image is only included when one was
 * picked -- otherwise an edit would blank the banner it is editing.
 */
function submit() {
    const payload = { ...form };

    if (payload.image === null) {
        delete payload.image;
    }

    return crud.save(payload);
}

onMounted(crud.load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-bold text-ink-700">Banners</h1>
                <p class="text-sm text-slate-500">The rotating panel at the top of the homepage.</p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Add banner
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="grid gap-4 sm:grid-cols-2">
            <div v-for="n in 4" :key="n" class="h-48 animate-pulse rounded-lg bg-white" />
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <article v-for="banner in banners" :key="banner.id" class="card overflow-hidden">
                <img :src="banner.url" :alt="banner.headline ?? 'Banner'" class="aspect-video w-full object-cover">

                <div class="flex items-start gap-3 p-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink-700">{{ banner.headline ?? 'No headline' }}</p>
                        <p class="truncate text-xs text-slate-500">{{ banner.link_url ?? 'Not linked' }}</p>
                        <p class="text-xs text-slate-400">Position {{ banner.position }}</p>
                    </div>

                    <div v-if="canManage()" class="flex shrink-0 flex-col items-end">
                        <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(banner)">Edit</button>
                        <button
                            type="button"
                            class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                            @click="crud.remove(banner)"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </article>

            <p v-if="banners.length === 0" class="col-span-full py-8 text-center text-slate-400">No banners yet.</p>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-md overflow-auto p-6" @submit.prevent="submit">
                <h2 class="font-heading text-lg font-bold text-ink-700">
                    {{ editing ? 'Edit banner' : 'New banner' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="banner-image">
                            Image {{ editing ? '(leave empty to keep the current one)' : '' }}
                        </label>
                        <input
                            id="banner-image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="field-input"
                            @change="chooseImage"
                        >
                        <span v-if="errors.image" class="field-error">{{ errors.image[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="banner-headline">Headline</label>
                        <input id="banner-headline" v-model="form.headline" class="field-input">
                        <span v-if="errors.headline" class="field-error">{{ errors.headline[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="banner-link">Link URL</label>
                        <input id="banner-link" v-model="form.link_url" type="url" class="field-input" placeholder="https://">
                        <span v-if="errors.link_url" class="field-error">{{ errors.link_url[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="banner-position">Position</label>
                        <input id="banner-position" v-model.number="form.position" type="number" min="0" class="field-input">
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save banner' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
