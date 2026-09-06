<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { catalog } from '@/api';
import { useReferenceStore } from '@/stores/reference';
import ProductCard from '@/components/ProductCard.vue';
import PaginationBar from '@/components/PaginationBar.vue';

/**
 * The catalogue.
 *
 * Filters compose on the server, and the branch filter is just another branch
 * id, so a newly opened branch is browsable the moment it has stock.
 */
const reference = useReferenceStore();
const route = useRoute();
const router = useRouter();

const products = ref([]);
const meta = ref(null);
const loading = ref(true);

const search = ref(route.query.q ?? '');
const selectedCategories = ref([].concat(route.query.category ?? []));
const selectedBrands = ref([].concat(route.query.brand ?? []));
const sort = ref(route.query.sort ?? 'latest');
const page = ref(Number.parseInt(route.query.page ?? '1', 10));

const branchSlug = computed(() => reference.selectedBranchSlug);

async function load() {
    loading.value = true;

    try {
        const response = await catalog.products({
            branch: branchSlug.value ?? undefined,
            category: selectedCategories.value,
            brand: selectedBrands.value,
            q: search.value || undefined,
            sort: sort.value,
            page: page.value,
        });

        products.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

function syncUrl() {
    router.replace({
        name: 'shop',
        query: {
            q: search.value || undefined,
            category: selectedCategories.value.length ? selectedCategories.value : undefined,
            brand: selectedBrands.value.length ? selectedBrands.value : undefined,
            sort: sort.value === 'latest' ? undefined : sort.value,
            page: page.value === 1 ? undefined : page.value,
        },
    });
}

function resetToFirstPage() {
    page.value = 1;
}

function clearFilters() {
    search.value = '';
    selectedCategories.value = [];
    selectedBrands.value = [];
    sort.value = 'latest';
    resetToFirstPage();
}

const hasFilters = computed(
    () => search.value !== '' || selectedCategories.value.length > 0 || selectedBrands.value.length > 0,
);

watch([selectedCategories, selectedBrands, sort, branchSlug], () => {
    resetToFirstPage();
    syncUrl();
    load();
}, { deep: true });

watch(page, () => {
    syncUrl();
    load();
});

watch(() => reference.loaded, (loaded) => {
    if (loaded) {
        load();
    }
}, { immediate: true });

function submitSearch() {
    resetToFirstPage();
    syncUrl();
    load();
}
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-10">
        <header class="mb-8">
            <h1 class="font-display text-4xl text-ink-700">Shop</h1>
            <p class="mt-1 text-sm text-slate-500">
                Showing stock for
                <strong class="text-ink-700">{{ reference.selectedBranch?.name ?? 'all branches' }}</strong>
            </p>
        </header>

        <div class="grid gap-8 lg:grid-cols-[16rem_1fr]">
            <aside class="space-y-6">
                <form class="flex gap-2" @submit.prevent="submitSearch">
                    <input v-model="search" type="search" placeholder="Search products" class="field-input">
                    <button type="submit" class="btn-primary px-3">Go</button>
                </form>

                <section>
                    <h2 class="mb-2 font-heading text-sm font-bold uppercase tracking-wide text-slate-500">Categories</h2>
                    <label
                        v-for="category in reference.flatCategories"
                        :key="category.id"
                        class="flex items-center gap-2 py-1 text-sm"
                    >
                        <input v-model="selectedCategories" type="checkbox" :value="category.slug" class="rounded">
                        <span :class="category.parent_id ? 'pl-2 text-slate-600' : 'font-semibold text-ink-700'">
                            {{ category.name }}
                        </span>
                    </label>
                </section>

                <section>
                    <h2 class="mb-2 font-heading text-sm font-bold uppercase tracking-wide text-slate-500">Brands</h2>
                    <label v-for="brand in reference.brands" :key="brand.id" class="flex items-center gap-2 py-1 text-sm">
                        <input v-model="selectedBrands" type="checkbox" :value="brand.slug" class="rounded">
                        <span>{{ brand.name }}</span>
                    </label>
                </section>

                <button v-if="hasFilters" type="button" class="btn-secondary w-full" @click="clearFilters">
                    Clear filters
                </button>
            </aside>

            <section>
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-slate-500">
                        <span v-if="meta">{{ meta.total }} product{{ meta.total === 1 ? '' : 's' }}</span>
                    </p>

                    <label class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500">Sort</span>
                        <select v-model="sort" class="rounded border border-slate-300 px-2 py-1.5 text-sm">
                            <option value="latest">Newest</option>
                            <option value="price_asc">Price: low to high</option>
                            <option value="price_desc">Price: high to low</option>
                            <option value="name_asc">Name: A to Z</option>
                        </select>
                    </label>
                </div>

                <div v-if="loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="n in 6" :key="n" class="h-80 animate-pulse rounded-lg bg-slate-100" />
                </div>

                <div v-else-if="products.length === 0" class="card p-12 text-center">
                    <p class="font-heading text-lg font-semibold text-ink-700">Nothing matches those filters</p>
                    <p class="mt-1 text-sm text-slate-500">Try a different branch, category or search term.</p>
                </div>

                <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>

                <PaginationBar :meta="meta" @change="page = $event" />
            </section>
        </div>
    </div>
</template>
