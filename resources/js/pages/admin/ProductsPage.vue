<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { admin, toFormData } from '@/api';
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
 *
 * Create/edit is a second dialog rather than `useResourceCrud`: that composable
 * assumes a flat, unpaginated list, but this page is already paginated and
 * filtered, and the form body is multipart (images), not a plain JSON payload.
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

const BLANK_PRODUCT = {
    sku: '', name: '', slug: '', brand_id: '', category_id: '',
    short_description: '', description: '', price: '',
    length_cm: '', width_cm: '', height_cm: '', weight_kg: '',
    is_featured: false, is_bundle: false, requires_delivery: true,
    status: 'draft', images: null,
};

const editing = ref(null);
const showForm = ref(false);
const saving = ref(false);
const errors = reactive({});
const form = reactive({ ...BLANK_PRODUCT });

function clearErrors() {
    Object.keys(errors).forEach((key) => delete errors[key]);
}

function startCreate() {
    editing.value = null;
    Object.assign(form, BLANK_PRODUCT);
    clearErrors();
    showForm.value = true;
}

function startEdit(product) {
    editing.value = product;
    Object.assign(form, BLANK_PRODUCT, {
        sku: product.sku,
        name: product.name,
        slug: product.slug,
        brand_id: product.brand?.id ?? '',
        category_id: product.category?.id ?? '',
        short_description: product.short_description ?? '',
        description: product.description ?? '',
        price: product.price,
        length_cm: product.dimensions?.length_cm ?? '',
        width_cm: product.dimensions?.width_cm ?? '',
        height_cm: product.dimensions?.height_cm ?? '',
        weight_kg: product.dimensions?.weight_kg ?? '',
        is_featured: product.is_featured,
        is_bundle: product.is_bundle,
        requires_delivery: product.requires_delivery,
        status: product.status,
        images: null,
    });
    clearErrors();
    showForm.value = true;
}

function chooseImages(event) {
    form.images = event.target.files?.length ? event.target.files : null;
}

async function submitProduct() {
    saving.value = true;
    notice.value = null;
    clearErrors();

    const payload = { ...form };

    if (payload.images === null) {
        delete payload.images;
    }

    try {
        const body = toFormData(payload);

        if (editing.value === null) {
            await admin.createProduct(body);
            notice.value = `${form.name} created.`;
        } else {
            await admin.updateProduct(editing.value.slug, body);
            notice.value = `${form.name} updated.`;
        }

        showForm.value = false;
        await load();
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});

        if (Object.keys(failure.errors ?? {}).length === 0) {
            notice.value = failure.message;
        }
    } finally {
        saving.value = false;
    }
}

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
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Products</h1>
                <p class="text-sm text-ink-400">Stock is tracked per branch.</p>
            </div>

            <button v-if="auth.can('products.manage')" type="button" class="btn-primary" @click="startCreate">
                Add product
            </button>
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
            <div v-for="n in 5" :key="n" class="h-20 animate-pulse rounded-2xl bg-white" />
        </div>

        <div v-else class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Total stock</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="product in products" :key="product.id" class="hover:bg-ink-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="size-10 shrink-0 overflow-hidden rounded bg-ink-100">
                                    <img v-if="product.images?.[0]" :src="product.images[0].url" :alt="product.name" class="size-full object-cover">
                                </div>
                                <div>
                                    <p class="font-semibold text-ink-900">{{ product.name }}</p>
                                    <p class="text-xs text-ink-400">{{ product.sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ product.category?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ formatMoney(product.price) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-ink-900">
                            {{ (product.stock ?? []).reduce((total, row) => total + row.quantity, 0) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                :class="{
                                    'bg-emerald-50 text-emerald-700': product.status === 'active',
                                    'bg-ink-100 text-ink-400': product.status === 'draft',
                                    'bg-amber-50 text-amber-700': product.status === 'archived',
                                }"
                            >{{ product.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button v-if="auth.can('products.manage')" type="button" class="btn-ghost px-2 py-1 text-xs" @click="startEdit(product)">
                                Edit
                            </button>
                            <button v-if="auth.can('stock.manage')" type="button" class="btn-ghost px-2 py-1 text-xs" @click="openStock(product)">
                                Stock
                            </button>
                            <button
                                v-if="auth.can('products.manage') && product.status !== 'archived'"
                                type="button"
                                class="px-2 py-1 text-xs text-ink-400 hover:underline"
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
                <h2 class="font-heading text-lg font-semibold text-ink-900">Stock &mdash; {{ stockFor.name }}</h2>
                <p class="mt-1 text-sm text-ink-400">One row per branch, including any branch added recently.</p>

                <div class="mt-4 space-y-2">
                    <div v-for="row in stockRows" :key="row.branch_id" class="grid grid-cols-[1fr_5rem_5rem] items-center gap-3">
                        <span class="text-sm text-ink-600">{{ row.branch_name }}</span>
                        <input v-model="row.quantity" type="number" min="0" class="field-input" aria-label="Quantity">
                        <input v-model="row.low_stock_threshold" type="number" min="0" class="field-input" aria-label="Low stock threshold">
                    </div>
                    <p class="text-xs text-ink-400">Columns: on hand, low-stock threshold.</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="stockFor = null">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="savingStock">
                        {{ savingStock ? 'Saving…' : 'Save stock' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Create / edit. Stock and bundle composition stay in their own
             dedicated tools above rather than duplicating them here. -->
        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/40 p-4">
            <form class="card my-8 max-h-full w-full max-w-2xl overflow-auto p-6" @submit.prevent="submitProduct">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.name}` : 'New product' }}
                </h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label" for="product-sku">SKU</label>
                        <input id="product-sku" v-model="form.sku" class="field-input">
                        <span v-if="errors.sku" class="field-error">{{ errors.sku[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="product-name">Name</label>
                        <input id="product-name" v-model="form.name" class="field-input">
                        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="product-slug">Slug</label>
                        <input id="product-slug" v-model="form.slug" class="field-input" placeholder="Derived from the name">
                        <span v-if="errors.slug" class="field-error">{{ errors.slug[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="product-price">Price</label>
                        <input id="product-price" v-model="form.price" type="number" min="0" step="0.01" class="field-input">
                        <span v-if="errors.price" class="field-error">{{ errors.price[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="product-brand">Brand</label>
                        <select id="product-brand" v-model="form.brand_id" class="field-input">
                            <option value="">None</option>
                            <option v-for="brand in reference.brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="product-category">Category</label>
                        <select id="product-category" v-model="form.category_id" class="field-input">
                            <option value="">None</option>
                            <option v-for="category in reference.flatCategories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="field-label" for="product-status">Status</label>
                        <select id="product-status" v-model="form.status" class="field-input">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-ink-600">
                            <input v-model="form.is_featured" type="checkbox" class="rounded border-ink-300">
                            Featured
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-600">
                            <input v-model="form.is_bundle" type="checkbox" class="rounded border-ink-300">
                            Bundle
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-600">
                            <input v-model="form.requires_delivery" type="checkbox" class="rounded border-ink-300">
                            Requires delivery
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="product-short">Short description</label>
                        <textarea id="product-short" v-model="form.short_description" rows="2" class="field-input" />
                        <span v-if="errors.short_description" class="field-error">{{ errors.short_description[0] }}</span>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="product-description">Description</label>
                        <textarea id="product-description" v-model="form.description" rows="4" class="field-input" />
                    </div>

                    <div>
                        <label class="field-label" for="product-length">Length (cm)</label>
                        <input id="product-length" v-model="form.length_cm" type="number" min="0" step="0.01" class="field-input">
                    </div>

                    <div>
                        <label class="field-label" for="product-width">Width (cm)</label>
                        <input id="product-width" v-model="form.width_cm" type="number" min="0" step="0.01" class="field-input">
                    </div>

                    <div>
                        <label class="field-label" for="product-height">Height (cm)</label>
                        <input id="product-height" v-model="form.height_cm" type="number" min="0" step="0.01" class="field-input">
                    </div>

                    <div>
                        <label class="field-label" for="product-weight">Weight (kg)</label>
                        <input id="product-weight" v-model="form.weight_kg" type="number" min="0" step="0.01" class="field-input">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label" for="product-images">
                            Images {{ editing ? '(adds to the existing gallery)' : '' }}
                        </label>
                        <input
                            id="product-images"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            class="field-input"
                            @change="chooseImages"
                        >
                        <span v-if="errors['images.0']" class="field-error">{{ errors['images.0'][0] }}</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save product' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
