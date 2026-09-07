<script setup>
import { onMounted, ref } from 'vue';
import { admin, toFormData } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useResourceCrud } from '@/support/useResourceCrud';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatDate } from '@/support/format';

/**
 * News posts.
 *
 * Body copy is plain text or simple HTML: the previous system loaded CKEditor 4
 * -- end of life, and initialised on pages that had no editor to attach to --
 * and stored whatever it produced unsanitised.
 */
const auth = useAuthStore();

const page = ref(1);
const meta = ref(null);

const crud = useResourceCrud({
    list: async () => {
        const response = await admin.posts({ page: page.value });

        meta.value = response.meta;

        return response.data;
    },
    create: (payload) => admin.createPost(payload),
    update: (slug, payload) => admin.updatePost(slug, payload),
    remove: (slug) => admin.deletePost(slug),
    keyOf: (post) => post.slug,
    blank: {
        title: '', slug: '', author: '', excerpt: '', body: '',
        video_url: '', published_at: '', thumbnail: null,
    },
    toForm: (post) => ({
        title: post.title,
        slug: post.slug,
        author: post.author ?? '',
        excerpt: post.excerpt ?? '',
        body: post.body ?? '',
        video_url: post.video_url ?? '',
        published_at: post.published_at ? post.published_at.slice(0, 10) : '',
        thumbnail: null,
    }),
    savedMessage: (post, created) => `${post.title} ${created ? 'published' : 'updated'}.`,
});

const { items: posts, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('content.manage');

function chooseThumbnail(event) {
    form.thumbnail = event.target.files?.[0] ?? null;
}

function submit() {
    return crud.save(form.thumbnail ? toFormData({ ...form }) : { ...form, thumbnail: undefined });
}

async function goToPage(next) {
    page.value = next;
    await crud.load();
}

onMounted(crud.load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">News</h1>
                <p class="text-sm text-ink-400">Posts shown on the storefront news page.</p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Write post
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 5" :key="n" class="h-20 animate-pulse rounded-2xl bg-white" />
        </div>

        <template v-else>
            <div class="card overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                        <tr>
                            <th class="px-4 py-3">Post</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3">Published</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        <tr v-for="post in posts" :key="post.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="size-12 shrink-0 overflow-hidden rounded bg-ink-100">
                                        <img
                                            v-if="post.thumbnail_url"
                                            :src="post.thumbnail_url"
                                            :alt="post.title"
                                            class="size-full object-cover"
                                        >
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink-900">{{ post.title }}</p>
                                        <p class="truncate text-xs text-ink-400">/{{ post.slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-ink-500">{{ post.author ?? '—' }}</td>
                            <td class="px-4 py-3 text-ink-500">
                                {{ post.published_at ? formatDate(post.published_at) : 'Draft' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <template v-if="canManage()">
                                    <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(post)">
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                                        @click="crud.remove(post)"
                                    >
                                        Delete
                                    </button>
                                </template>
                            </td>
                        </tr>
                        <tr v-if="posts.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-ink-400">No posts yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationBar v-if="meta" class="mt-4" :meta="meta" @change="goToPage" />
        </template>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-2xl overflow-auto p-6" @submit.prevent="submit">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.title}` : 'New post' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="post-title">Title</label>
                        <input id="post-title" v-model="form.title" class="field-input">
                        <span v-if="errors.title" class="field-error">{{ errors.title[0] }}</span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="post-author">Author</label>
                            <input id="post-author" v-model="form.author" class="field-input">
                        </div>
                        <div>
                            <label class="field-label" for="post-published">Publish date</label>
                            <input id="post-published" v-model="form.published_at" type="date" class="field-input">
                            <span v-if="errors.published_at" class="field-error">{{ errors.published_at[0] }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="field-label" for="post-excerpt">Excerpt</label>
                        <textarea id="post-excerpt" v-model="form.excerpt" rows="2" class="field-input" />
                        <span v-if="errors.excerpt" class="field-error">{{ errors.excerpt[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="post-body">Body</label>
                        <textarea id="post-body" v-model="form.body" rows="8" class="field-input" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label" for="post-video">Video URL</label>
                            <input id="post-video" v-model="form.video_url" type="url" class="field-input">
                            <span v-if="errors.video_url" class="field-error">{{ errors.video_url[0] }}</span>
                        </div>
                        <div>
                            <label class="field-label" for="post-thumbnail">Thumbnail</label>
                            <input
                                id="post-thumbnail"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="field-input"
                                @change="chooseThumbnail"
                            >
                            <span v-if="errors.thumbnail" class="field-error">{{ errors.thumbnail[0] }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save post' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
