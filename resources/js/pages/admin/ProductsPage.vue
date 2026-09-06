<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import PaginationBar from '@/components/PaginationBar.vue';
import { formatMoney } from '@/support/format';

/**
 * Product list with per-branch stock.
 *
 * The stock editor renders one row per branch straight from the branches list,
 * so a branch opened last week appears here automatically. The previous system
 * had seven fixed stock columns and a hand-written input for each.
 */
const auth = useAuthStore();
const reference = useReferenceStore();

const products = ref([]);
const meta = ref(null);
const loading = ref(true);
const stockFor = ref(null);
const savingStock = ref(false);
const notice = ref(null);

const filters = reactive({ q: '', category_id: '', status: '', page: 1 });

const stockRows = ref([]);

async function load() {
    loading.value = true;

    try {
        const response = await admin.products({
            q: filters.q || undefined,
            category_id: filters.category_id || undefined,
            status: filters.status || undefined,
            page: filters.page,
        });

        products.value = response.data;
        meta.value = response.meta;
    } finally {
        loading.value = false;
    }
}

/**
 * Build a row per branch, defaulting anything the product has no row for yet.
 */
function openStock(product) {
    stockFor.value = product;

    stockRows.value = reference.branches.map((branch) => {
        const existing = product.stock?.find((row) => row.branch_id === branch.id);

        return {
            branch_id: branch.id,
            branch_name: branch.name,
            quantity: existing?.quantity ?? 0,
            low_stock_threshold: existing?.low_stock_threshold ?? 0,
        };
    });
}

async function saveStock() {
    savingStock.value = true;

    try {
        for (const row of stockRows.value) {
            await admin.setStock(stockFor.value.slug, {
                branch_id: row.branch_id,
                quantity: Number(row.quantity),
                low_stock_threshold: Number(row.low_stock_threshold),
            });
        }

        notice.value = `Stock updated for ${stockFor.value.name}.`;
        stockFor.value = null;
        await load();
    } finally {
        savingStock.value = false;
    }
}

async function archive(product) {
    await admin.archiveProduct(product.slug);
    await load();
}

watch(() => [filters.category_id, filters.status], () => {
    filters.page = 1;
    load();
});

watch(() => filters.page, load);

onMounted(load);
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-bold text-ink-700">Products</h1>
            <p class="text-sm text-slate-500">Stock is tracked per branch.</p>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div class="card mb-4 flex flex-wrap items-end gap-3 p-4">
            <form class="flex gap-2" @submit.prevent="filters.page = 1; load()">
                <input v-model="filters.q" type="search" placeholder="Name or SKU" class="field-input w-64">
                <button type="submit" class="btn-primary px-4">Search</button>
            </form>

            <label class="text-sm">
                <span class="field-label">Category</span>
                <select v-model="filters.category_id" class="field-input">
                    <option value="">All</option>
                    <option v-for="category in reference.flatCategories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </label>

            <label class="text-sm">
                <span class="field-label">Status</span>
                <select v-model="filters.status" class="field-input">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </label>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 5" :key="n" class="h-20 animate-pulse rounded-lg bg-white" />
        </div>

        <div v-else class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Total stock</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="product in products" :key="product.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="size-10 shrink-0 overflow-hidden rounded bg-slate-100">
                                    <img v-if="product.images?.[0]" :src="product.images[0].url" :alt="product.name" class="size-full object-cover">
                                </div>
                                <div>
                                    <p class="font-semibold text-ink-700">{{ product.name }}</p>
                                    <p class="text-xs text-slate-400">{{ product.sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ product.category?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ formatMoney(product.price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-ink-700">
                            {{ (product.stock ?? []).reduce((total, row) => total + row.quantity, 0) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                :class="{
                                    'bg-emerald-50 text-emerald-700': product.status === 'active',
                                    'bg-slate-100 text-slate-500': product.status === 'draft',
                                    'bg-amber-50 text-amber-700': product.status === 'archived',
                                }"
                            >{{ product.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="auth.can('stock.manage')" type="button" class="btn-ghost px-2 py-1 text-xs" @click="openStock(product)">
                                Stock
                            </button>
                            <button
                                v-if="auth.can('products.manage') && product.status !== 'archived'"
                                type="button"
                                class="px-2 py-1 text-xs text-slate-500 hover:underline"
                                @click="archive(product)"
                            >Archive</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationBar :meta="meta" @change="filters.page = $event" />

        <!-- Per-branch stock editor, generated from the branch list -->
        <div v-if="stockFor" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="stockFor = null">
            <form class="card max-h-full w-full max-w-lg overflow-auto p-6" @submit.prevent="saveStock">
                <h2 class="font-heading text-lg font-bold text-ink-700">Stock &mdash; {{ stockFor.name }}</h2>
                <p class="mt-1 text-sm text-slate-500">One row per branch, including any branch added recently.</p>

                <div class="mt-4 space-y-2">
                    <div v-for="row in stockRows" :key="row.branch_id" class="grid grid-cols-[1fr_5rem_5rem] items-center gap-3">
                        <span class="text-sm text-slate-700">{{ row.branch_name }}</span>
                        <input v-model="row.quantity" type="number" min="0" class="field-input" aria-label="Quantity">
                        <input v-model="row.low_stock_threshold" type="number" min="0" class="field-input" aria-label="Low stock threshold">
                    </div>
                    <p class="text-xs text-slate-400">Columns: on hand, low-stock threshold.</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="stockFor = null">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="savingStock">
                        {{ savingStock ? 'Saving…' : 'Save stock' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
