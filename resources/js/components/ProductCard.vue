<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { formatMoney } from '@/support/format';

const props = defineProps({
    product: { type: Object, required: true },
});

const image = computed(() => props.product.images?.[0]?.url ?? null);
const outOfStock = computed(() => props.product.available_quantity === 0);
</script>

<template>
    <RouterLink
        :to="{ name: 'product', params: { slug: product.slug } }"
        class="card group flex flex-col overflow-hidden transition hover:shadow-md"
    >
        <div class="aspect-square overflow-hidden bg-slate-100">
            <img
                v-if="image"
                :src="image"
                :alt="product.name"
                loading="lazy"
                class="size-full object-cover transition group-hover:scale-105"
            >
            <div v-else class="grid size-full place-items-center text-sm text-slate-400">No image</div>
        </div>

        <div class="flex flex-1 flex-col p-4">
            <p v-if="product.brand" class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ product.brand.name }}
            </p>
            <p class="mt-1 line-clamp-2 font-heading text-sm font-semibold text-ink-700">{{ product.name }}</p>

            <div class="mt-auto pt-3">
                <p class="font-heading text-lg font-bold text-brand-500">{{ formatMoney(product.price) }}</p>
                <p v-if="outOfStock" class="mt-1 text-xs font-semibold text-red-600">Out of stock at this branch</p>
                <p v-else-if="product.available_quantity != null" class="mt-1 text-xs text-slate-500">
                    {{ product.available_quantity }} in stock
                </p>
            </div>
        </div>
    </RouterLink>
</template>
