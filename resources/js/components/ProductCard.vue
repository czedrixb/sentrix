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
        class="card group flex flex-col overflow-hidden hover:-translate-y-1 hover:border-brand-300 hover:shadow-[0_12px_40px_rgba(14,15,17,0.07)]"
    >
        <div class="aspect-square overflow-hidden bg-ink-50">
            <img
                v-if="image"
                :src="image"
                :alt="product.name"
                loading="lazy"
                class="size-full object-cover transition-transform duration-700 ease-soft group-hover:scale-105"
            >
            <div v-else class="grid size-full place-items-center text-sm text-ink-300">No image</div>
        </div>

        <div class="flex flex-1 flex-col p-5">
            <p v-if="product.brand" class="eyebrow">{{ product.brand.name }}</p>
            <p class="mt-2 line-clamp-2 font-heading text-sm font-medium leading-snug text-ink-900">
                {{ product.name }}
            </p>

            <div class="mt-auto flex items-end justify-between gap-3 pt-4">
                <p class="font-heading text-lg font-medium text-ink-900">{{ formatMoney(product.price) }}</p>

                <span
                    v-if="outOfStock"
                    class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-600"
                >Out of stock</span>
                <span
                    v-else-if="product.available_quantity != null"
                    class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-medium text-brand-700"
                >{{ product.available_quantity }} in stock</span>
            </div>
        </div>
    </RouterLink>
</template>
