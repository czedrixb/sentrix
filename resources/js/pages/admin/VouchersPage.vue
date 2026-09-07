<script setup>
import { onMounted, ref } from 'vue';
import { admin } from '@/api';
import { formatMoney, formatDate } from '@/support/format';

const vouchers = ref([]);
const loading = ref(true);

const typeLabels = {
    percentage: 'Percentage off',
    fixed: 'Fixed amount off',
    percentage_min_qty: 'Percentage, min quantity',
    fixed_min_qty: 'Fixed, min quantity',
};

onMounted(async () => {
    try {
        vouchers.value = (await admin.vouchers()).data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Vouchers</h1>
            <p class="text-sm text-ink-400">
                Usage limits are enforced on redemption, and every redemption is recorded against its order.
            </p>
        </header>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-2xl bg-white" />
        </div>

        <p v-else-if="vouchers.length === 0" class="card p-12 text-center text-ink-400">No vouchers yet.</p>

        <div v-else class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Value</th>
                        <th class="px-4 py-3 text-right">Min qty</th>
                        <th class="px-4 py-3 text-right">Used</th>
                        <th class="px-4 py-3">Window</th>
                        <th class="px-4 py-3">Active</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="voucher in vouchers" :key="voucher.id">
                        <td class="px-4 py-3 font-semibold text-ink-900">{{ voucher.code }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ typeLabels[voucher.type] ?? voucher.type }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ voucher.type.startsWith('percentage') ? `${voucher.value}%` : formatMoney(voucher.value) }}
                        </td>
                        <td class="px-4 py-3 text-right text-ink-400">{{ voucher.min_quantity ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ voucher.times_used }}<span v-if="voucher.usage_limit" class="text-ink-400"> / {{ voucher.usage_limit }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">
                            <span v-if="voucher.starts_at || voucher.ends_at">
                                {{ formatDate(voucher.starts_at) || '—' }} to {{ formatDate(voucher.ends_at) || '—' }}
                            </span>
                            <span v-else>Always</span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="voucher.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-ink-100 text-ink-400'"
                            >{{ voucher.is_active ? 'Yes' : 'No' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
