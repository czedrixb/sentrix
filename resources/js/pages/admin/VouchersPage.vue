<script setup>
import { onMounted } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useResourceCrud } from '@/support/useResourceCrud';
import { formatMoney, formatDate } from '@/support/format';

const auth = useAuthStore();

const typeLabels = {
    percentage: 'Percentage off',
    fixed: 'Fixed amount off',
    percentage_min_qty: 'Percentage, min quantity',
    fixed_min_qty: 'Fixed, min quantity',
};

const minQtyTypes = ['percentage_min_qty', 'fixed_min_qty'];

/** ISO timestamp -> the yyyy-mm-dd shape a date input needs. */
function toDateInput(value) {
    return value ? value.slice(0, 10) : '';
}

const crud = useResourceCrud({
    list: () => admin.vouchers().then((response) => response.data),
    create: (payload) => admin.createVoucher(payload),
    update: (code, payload) => admin.updateVoucher(code, payload),
    remove: (code) => admin.deleteVoucher(code),
    keyOf: (voucher) => voucher.code,
    blank: {
        code: '', description: '', type: 'percentage', value: '',
        min_quantity: '', min_subtotal: '', usage_limit: '',
        starts_at: '', ends_at: '', is_active: true,
    },
    toForm: (voucher) => ({
        code: voucher.code,
        description: voucher.description ?? '',
        type: voucher.type,
        value: voucher.value,
        min_quantity: voucher.min_quantity ?? '',
        min_subtotal: voucher.min_subtotal ?? '',
        usage_limit: voucher.usage_limit ?? '',
        starts_at: toDateInput(voucher.starts_at),
        ends_at: toDateInput(voucher.ends_at),
        is_active: voucher.is_active,
    }),
    savedMessage: (voucher, created) => created ? `${voucher.code} created.` : `${voucher.code} updated.`,
});

const { items: vouchers, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('vouchers.manage');
const needsMinQuantity = () => minQtyTypes.includes(form.type);

/**
 * Blank optional numbers become null rather than an empty string, and
 * `min_quantity` is dropped entirely for types that do not use it so it
 * cannot trip the "required when percentage_min_qty/fixed_min_qty" rule.
 */
function submit() {
    const payload = { ...form };

    ['min_quantity', 'min_subtotal', 'usage_limit', 'starts_at', 'ends_at'].forEach((key) => {
        if (payload[key] === '') {
            payload[key] = null;
        }
    });

    if (!needsMinQuantity()) {
        payload.min_quantity = null;
    }

    return crud.save(payload);
}

onMounted(crud.load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Vouchers</h1>
                <p class="text-sm text-ink-400">
                    Usage limits are enforced on redemption, and every redemption is recorded against its order.
                </p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Add voucher
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

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
                        <th class="px-4 py-3" />
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
                        <td class="px-4 py-3 text-right">
                            <template v-if="canManage()">
                                <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(voucher)">
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                                    @click="crud.remove(voucher)"
                                >
                                    Remove
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/40 p-4">
            <form class="card my-8 max-h-full w-full max-w-lg overflow-auto p-6" @submit.prevent="submit">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.code}` : 'New voucher' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="voucher-code">Code</label>
                        <input id="voucher-code" v-model="form.code" class="field-input uppercase">
                        <span v-if="errors.code" class="field-error">{{ errors.code[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="voucher-description">Description</label>
                        <input id="voucher-description" v-model="form.description" class="field-input">
                        <span v-if="errors.description" class="field-error">{{ errors.description[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="voucher-type">Type</label>
                        <select id="voucher-type" v-model="form.type" class="field-input">
                            <option v-for="(label, value) in typeLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                        <span v-if="errors.type" class="field-error">{{ errors.type[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="voucher-value">
                            Value {{ form.type.startsWith('percentage') ? '(%)' : '(₱)' }}
                        </label>
                        <input id="voucher-value" v-model="form.value" type="number" min="0" step="0.01" class="field-input">
                        <span v-if="errors.value" class="field-error">{{ errors.value[0] }}</span>
                    </div>

                    <div v-if="needsMinQuantity()">
                        <label class="field-label" for="voucher-min-quantity">Minimum quantity</label>
                        <input id="voucher-min-quantity" v-model="form.min_quantity" type="number" min="1" class="field-input">
                        <span v-if="errors.min_quantity" class="field-error">{{ errors.min_quantity[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="voucher-min-subtotal">Minimum subtotal (₱)</label>
                        <input id="voucher-min-subtotal" v-model="form.min_subtotal" type="number" min="0" step="0.01" class="field-input">
                        <span v-if="errors.min_subtotal" class="field-error">{{ errors.min_subtotal[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="voucher-usage-limit">Usage limit</label>
                        <input id="voucher-usage-limit" v-model="form.usage_limit" type="number" min="1" class="field-input" placeholder="Unlimited">
                        <span v-if="errors.usage_limit" class="field-error">{{ errors.usage_limit[0] }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="field-label" for="voucher-starts">Starts</label>
                            <input id="voucher-starts" v-model="form.starts_at" type="date" class="field-input">
                            <span v-if="errors.starts_at" class="field-error">{{ errors.starts_at[0] }}</span>
                        </div>
                        <div>
                            <label class="field-label" for="voucher-ends">Ends</label>
                            <input id="voucher-ends" v-model="form.ends_at" type="date" class="field-input">
                            <span v-if="errors.ends_at" class="field-error">{{ errors.ends_at[0] }}</span>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink-600">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-ink-300">
                        Active
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save voucher' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
