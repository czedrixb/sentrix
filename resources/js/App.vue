<script setup>
import { onMounted } from 'vue';
import { useReferenceStore } from '@/stores/reference';
import { useCartStore } from '@/stores/cart';

/**
 * Reference data and the cart are loaded once, then shared by every route.
 */
const reference = useReferenceStore();
const cart = useCartStore();

onMounted(async () => {
    await Promise.all([reference.load(), cart.load()]);
});
</script>

<template>
    <RouterView />
</template>
