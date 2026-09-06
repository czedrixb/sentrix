<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { catalog, content, reference as referenceApi } from '@/api';
import { useReferenceStore } from '@/stores/reference';
import ProductCard from '@/components/ProductCard.vue';
import { formatDate } from '@/support/format';

const reference = useReferenceStore();

const banners = ref([]);
const featured = ref([]);
const posts = ref([]);
const activeBanner = ref(0);

async function loadFeatured() {
    const response = await catalog.products({
        featured: 1,
        per_page: 8,
        branch: reference.selectedBranchSlug ?? undefined,
    });

    featured.value = response.data;
}

onMounted(async () => {
    banners.value = await referenceApi.banners();
    posts.value = (await content.posts({ per_page: 3 })).data;
});

watch(() => reference.selectedBranchSlug, loadFeatured, { immediate: true });
</script>

<template>
    <div>
        <!-- Hero -->
        <section class="bg-ink-700 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-16 lg:grid-cols-2">
                <div>
                    <h1 class="font-display text-5xl leading-tight text-white sm:text-6xl">
                        Everything your workplace runs on
                    </h1>
                    <p class="mt-4 max-w-lg text-ink-100">
                        Equipment, consumables, spare parts and accessories &mdash; stocked and ready across
                        {{ reference.pickupBranches.length }} branches.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <RouterLink :to="{ name: 'shop' }" class="btn-primary">Shop the catalogue</RouterLink>
                        <RouterLink :to="{ name: 'branches' }" class="btn bg-white/10 text-white hover:bg-white/20">
                            Find a branch
                        </RouterLink>
                    </div>
                </div>

                <div v-if="banners.length" class="relative overflow-hidden rounded-lg">
                    <img :src="banners[activeBanner].url" :alt="banners[activeBanner].headline ?? ''" class="aspect-[4/3] w-full object-cover">
                    <div v-if="banners.length > 1" class="absolute inset-x-0 bottom-3 flex justify-center gap-2">
                        <button
                            v-for="(banner, index) in banners"
                            :key="banner.id"
                            type="button"
                            class="size-2.5 rounded-full"
                            :class="index === activeBanner ? 'bg-brand-500' : 'bg-white/60'"
                            :aria-label="`Banner ${index + 1}`"
                            @click="activeBanner = index"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories, driven entirely by data -->
        <section class="mx-auto max-w-7xl px-4 py-14">
            <h2 class="font-heading text-2xl font-bold text-ink-700">Shop by category</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <RouterLink
                    v-for="category in reference.categories"
                    :key="category.id"
                    :to="{ name: 'shop', query: { category: category.slug } }"
                    class="card group p-6 transition hover:border-brand-500"
                >
                    <p class="font-heading text-lg font-bold text-ink-700 group-hover:text-brand-500">
                        {{ category.name }}
                    </p>
                    <p v-if="category.children?.length" class="mt-1 text-xs text-slate-500">
                        {{ category.children.map((child) => child.name).join(' · ') }}
                    </p>
                </RouterLink>
            </div>
        </section>

        <!-- Featured -->
        <section v-if="featured.length" class="bg-slate-50 py-14">
            <div class="mx-auto max-w-7xl px-4">
                <div class="flex items-end justify-between">
                    <div>
                        <h2 class="font-heading text-2xl font-bold text-ink-700">Featured</h2>
                        <p class="text-sm text-slate-500">In stock at {{ reference.selectedBranch?.name }}</p>
                    </div>
                    <RouterLink :to="{ name: 'shop' }" class="btn-ghost">View all</RouterLink>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <ProductCard v-for="product in featured" :key="product.id" :product="product" />
                </div>
            </div>
        </section>

        <!-- News -->
        <section v-if="posts.length" class="mx-auto max-w-7xl px-4 py-14">
            <div class="flex items-end justify-between">
                <h2 class="font-heading text-2xl font-bold text-ink-700">Latest news</h2>
                <RouterLink :to="{ name: 'news' }" class="btn-ghost">All news</RouterLink>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-3">
                <RouterLink
                    v-for="post in posts"
                    :key="post.id"
                    :to="{ name: 'post', params: { slug: post.slug } }"
                    class="card overflow-hidden transition hover:shadow-md"
                >
                    <div class="aspect-video bg-slate-100">
                        <img v-if="post.thumbnail_url" :src="post.thumbnail_url" :alt="post.title" class="size-full object-cover">
                    </div>
                    <div class="p-4">
                        <p class="text-xs text-slate-400">{{ formatDate(post.published_at) }}</p>
                        <p class="mt-1 font-heading font-semibold text-ink-700">{{ post.title }}</p>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ post.excerpt }}</p>
                    </div>
                </RouterLink>
            </div>
        </section>
    </div>
</template>
