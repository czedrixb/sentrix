<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { catalog } from '@/api';
import { useReferenceStore } from '@/stores/reference';
import { useCartStore } from '@/stores/cart';
import { formatMoney } from '@/support/format';
import QuantityStepper from '@/components/QuantityStepper.vue';
import ProductCard from '@/components/ProductCard.vue';

const route = useRoute();
const router = useRouter();
const reference = useReferenceStore();
const cart = useCartStore();

const product = ref(null);
const related = ref([]);
const loading = ref(true);
const activeImage = ref(0);
const quantity = ref(1);
const adding = ref(false);
const error = ref(null);

/**
 * A cart is tied to one branch, so once it holds something the product page
 * must quote stock for that branch rather than whatever is selected globally.
 */
const activeBranch = computed(() => (cart.isEmpty ? reference.selectedBranch : cart.branch));

const stockHere = computed(() => {
    if (!product.value || !activeBranch.value) {
        return 0;
    }

    return product.value.stock?.find((row) => row.branch_id === activeBranch.value.id)?.quantity ?? 0;
});

const inCartHere = computed(
    () => cart.items.find((item) => item.product?.id === product.value?.id)?.quantity ?? 0,
);

const remaining = computed(() => Math.max(0, stockHere.value - inCartHere.value));
const canAdd = computed(() => remaining.value > 0 && activeBranch.value !== null);

async function load() {
    loading.value = true;
    error.value = null;

    try {
        product.value = await catalog.product(route.params.slug);
        related.value = await catalog.related(route.params.slug, {
            branch: activeBranch.value?.slug ?? undefined,
        });
        activeImage.value = 0;
        quantity.value = 1;
    } catch (failure) {
        if (failure.status === 404) {
            router.replace({ name: 'not-found' });
        } else {
            error.value = failure.message;
        }
    } finally {
        loading.value = false;
    }
}

async function addToCart() {
    if (!canAdd.value) {
        return;
    }

    adding.value = true;
    error.value = null;

    try {
        await cart.addItem(product.value.id, activeBranch.value.id, quantity.value);
        quantity.value = 1;
    } catch (failure) {
        error.value = failure.message;
    } finally {
        adding.value = false;
    }
}

watch(() => route.params.slug, load, { immediate: true });
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-10">
        <div v-if="loading" class="grid gap-10 lg:grid-cols-2">
            <div class="aspect-square animate-pulse rounded-2xl bg-ink-100" />
            <div class="space-y-4">
                <div class="h-8 w-2/3 animate-pulse rounded bg-ink-100" />
                <div class="h-6 w-1/3 animate-pulse rounded bg-ink-100" />
                <div class="h-32 animate-pulse rounded bg-ink-100" />
            </div>
        </div>

        <div v-else-if="product" class="grid gap-10 lg:grid-cols-2">
            <section>
                <div class="aspect-square overflow-hidden rounded-2xl bg-ink-100">
                    <img
                        v-if="product.images?.length"
                        :src="product.images[activeImage].url"
                        :alt="product.images[activeImage].alt_text ?? product.name"
                        class="size-full object-cover"
                    >
                    <div v-else class="grid size-full place-items-center text-ink-400">No image</div>
                </div>

                <div v-if="product.images?.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                    <button
                        v-for="(image, index) in product.images"
                        :key="image.id"
                        type="button"
                        class="size-20 shrink-0 overflow-hidden rounded border-2"
                        :class="index === activeImage ? 'border-brand-400' : 'border-transparent'"
                        @click="activeImage = index"
                    >
                        <img :src="image.url" :alt="image.alt_text ?? ''" class="size-full object-cover">
                    </button>
                </div>
            </section>

            <section>
                <p v-if="product.brand" class="text-sm font-semibold uppercase tracking-wide text-ink-400">
                    {{ product.brand.name }}
                </p>
                <h1 class="mt-1 font-heading text-3xl font-semibold text-ink-900">{{ product.name }}</h1>
                <p class="mt-3 font-display text-4xl text-brand-600">{{ formatMoney(product.price) }}</p>

                <dl class="mt-4 space-y-1 text-sm text-ink-500">
                    <div class="flex gap-2"><dt class="font-semibold">SKU</dt><dd>{{ product.sku }}</dd></div>
                    <div v-if="product.category" class="flex gap-2">
                        <dt class="font-semibold">Category</dt><dd>{{ product.category.name }}</dd>
                    </div>
                </dl>

                <div v-if="product.bundle_items?.length" class="card mt-5 p-4">
                    <p class="font-heading text-sm font-semibold text-ink-900">This bundle includes</p>
                    <ul class="mt-2 space-y-1 text-sm text-ink-500">
                        <li v-for="item in product.bundle_items" :key="item.id">
                            {{ item.quantity }} &times; {{ item.label }}
                        </li>
                    </ul>
                </div>

                <!-- Stock is per branch, always. -->
                <div class="mt-6 rounded-2xl border border-ink-200 p-4">
                    <p class="text-sm text-ink-400">
                        Availability at <strong class="text-ink-900">{{ activeBranch?.name ?? 'no branch selected' }}</strong>
                    </p>

                    <p v-if="remaining > 0" class="mt-1 font-heading font-semibold text-emerald-600">
                        {{ remaining }} available
                    </p>
                    <p v-else class="mt-1 font-heading font-semibold text-red-600">
                        Out of stock at this branch
                    </p>

                    <p v-if="!cart.isEmpty" class="mt-2 text-xs text-ink-400">
                        Your cart is with {{ cart.branch?.name }}, so items must come from that branch.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <QuantityStepper v-model="quantity" :min="1" :max="Math.max(1, remaining)" :disabled="!canAdd" />
                        <button type="button" class="btn-primary" :disabled="!canAdd || adding" @click="addToCart">
                            {{ adding ? 'Adding…' : 'Add to cart' }}
                        </button>
                    </div>

                    <p v-if="error" class="field-error">{{ error }}</p>

                    <p v-if="product.requires_delivery" class="mt-3 text-xs text-ink-400">
                        This item is bulky, so delivery can be arranged at checkout.
                    </p>
                </div>

                <div v-if="product.description" class="prose-cms mt-8 text-sm" v-html="product.description" />
            </section>
        </div>

        <section v-if="related.length" class="mt-16">
            <h2 class="mb-5 font-heading text-2xl font-semibold text-ink-900">You might also like</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard v-for="item in related" :key="item.id" :product="item" />
            </div>
        </section>
    </div>
</template>
