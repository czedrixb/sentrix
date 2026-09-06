<script setup>
import { onMounted, reactive, ref } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';

/**
 * Gallery management.
 *
 * Uploads go up as one batch -- the endpoint takes an `images` array -- and a
 * delete removes the file from disk as well as the row. The previous version
 * read the wrong column when unlinking, so every gallery file it "deleted" was
 * left orphaned on the server.
 */
const auth = useAuthStore();

const images = ref([]);
const loading = ref(true);
const uploading = ref(false);
const notice = ref(null);
const errors = reactive({});

const form = reactive({ images: null, caption: '' });

const canManage = () => auth.can('content.manage');

async function load() {
    loading.value = true;

    try {
        images.value = await admin.gallery();
    } finally {
        loading.value = false;
    }
}

function chooseImages(event) {
    form.images = event.target.files;
}

async function upload() {
    if (!form.images || form.images.length === 0) {
        return;
    }

    uploading.value = true;
    notice.value = null;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const created = await admin.uploadGallery({ images: form.images, caption: form.caption });

        notice.value = `${created.length} image${created.length === 1 ? '' : 's'} added.`;
        form.images = null;
        form.caption = '';

        await load();
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});

        if (Object.keys(failure.errors ?? {}).length === 0) {
            notice.value = failure.message;
        }
    } finally {
        uploading.value = false;
    }
}

async function remove(image) {
    notice.value = null;

    try {
        const response = await admin.deleteGalleryImage(image.id);

        notice.value = response.message;
        await load();
    } catch (failure) {
        notice.value = failure.message;
    }
}

onMounted(load);
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-bold text-ink-700">Gallery</h1>
            <p class="text-sm text-slate-500">Photos shown on the storefront gallery page.</p>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <form v-if="canManage()" class="card mb-6 flex flex-wrap items-end gap-4 p-4" @submit.prevent="upload">
            <div class="min-w-56 flex-1">
                <label class="field-label" for="gallery-files">Images</label>
                <input
                    id="gallery-files"
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    class="field-input"
                    @change="chooseImages"
                >
                <span v-if="errors.images" class="field-error">{{ errors.images[0] }}</span>
                <span v-if="errors['images.0']" class="field-error">{{ errors['images.0'][0] }}</span>
            </div>

            <div class="min-w-48 flex-1">
                <label class="field-label" for="gallery-caption">Caption</label>
                <input id="gallery-caption" v-model="form.caption" class="field-input">
                <span v-if="errors.caption" class="field-error">{{ errors.caption[0] }}</span>
            </div>

            <button type="submit" class="btn-primary" :disabled="uploading">
                {{ uploading ? 'Uploading…' : 'Upload' }}
            </button>
        </form>

        <div v-if="loading" class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="n in 8" :key="n" class="aspect-4/3 animate-pulse rounded-lg bg-white" />
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <figure v-for="image in images" :key="image.id" class="card group relative overflow-hidden">
                <img :src="image.url" :alt="image.caption ?? 'Gallery image'" class="aspect-4/3 w-full object-cover">

                <figcaption class="flex items-center gap-2 p-2 text-xs text-slate-500">
                    <span class="min-w-0 flex-1 truncate">{{ image.caption ?? '—' }}</span>

                    <button
                        v-if="canManage()"
                        type="button"
                        class="shrink-0 font-semibold text-red-600 hover:underline"
                        @click="remove(image)"
                    >
                        Remove
                    </button>
                </figcaption>
            </figure>

            <p v-if="images.length === 0" class="col-span-full py-8 text-center text-slate-400">No images yet.</p>
        </div>
    </div>
</template>
