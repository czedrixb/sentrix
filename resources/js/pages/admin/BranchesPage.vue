<script setup>
import { onMounted, reactive, ref } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';

/**
 * Branch management.
 *
 * Creating a branch here is the entire process of opening a location: the
 * observer gives it a stock row for every product, the storefront picks it up
 * from the branches endpoint, enquiries can be addressed to it, and a manager
 * is assigned by setting their branch. Nothing is deployed.
 */
const auth = useAuthStore();

const branches = ref([]);
const loading = ref(true);
const saving = ref(false);
const editing = ref(null);
const showForm = ref(false);
const errors = reactive({});
const notice = ref(null);

const blank = {
    name: '',
    address: '',
    phone: '',
    sales_phone: '',
    email: '',
    map_embed: '',
    is_pickup_location: true,
    is_active: true,
    position: 0,
};

const form = reactive({ ...blank });

const canManage = () => auth.can('branches.manage');

async function load() {
    loading.value = true;

    try {
        branches.value = await admin.branches();
    } finally {
        loading.value = false;
    }
}

function startCreate() {
    editing.value = null;
    Object.assign(form, blank);
    Object.keys(errors).forEach((key) => delete errors[key]);
    showForm.value = true;
}

function startEdit(branch) {
    editing.value = branch;
    Object.assign(form, {
        name: branch.name,
        address: branch.address ?? '',
        phone: branch.phone ?? '',
        sales_phone: branch.sales_phone ?? '',
        email: branch.email ?? '',
        map_embed: branch.map_embed ?? '',
        is_pickup_location: branch.is_pickup_location,
        is_active: true,
        position: branch.position ?? 0,
    });
    Object.keys(errors).forEach((key) => delete errors[key]);
    showForm.value = true;
}

async function save() {
    saving.value = true;
    notice.value = null;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        if (editing.value) {
            await admin.updateBranch(editing.value.slug, { ...form });
            notice.value = `${form.name} updated.`;
        } else {
            await admin.createBranch({ ...form });
            notice.value = `${form.name} created, and every product now has a stock row for it.`;
        }

        showForm.value = false;
        await load();
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function remove(branch) {
    const response = await admin.deleteBranch(branch.slug);

    notice.value = response.message;
    await load();
}

onMounted(load);
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Branches</h1>
                <p class="text-sm text-ink-400">
                    Adding a branch takes effect everywhere immediately &mdash; no deployment required.
                </p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="startCreate">
                Add branch
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-2xl bg-white" />
        </div>

        <div v-else class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Pick-up</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="branch in branches" :key="branch.id">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-ink-900">{{ branch.name }}</p>
                            <p class="text-xs text-ink-400">{{ branch.address }}</p>
                        </td>
                        <td class="px-4 py-3 text-ink-500">
                            <p v-if="branch.phone">{{ branch.phone }}</p>
                            <p v-if="branch.email" class="text-xs text-ink-400">{{ branch.email }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="branch.is_pickup_location ? 'bg-emerald-50 text-emerald-700' : 'bg-ink-100 text-ink-400'"
                            >{{ branch.is_pickup_location ? 'Yes' : 'Warehouse' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <template v-if="canManage()">
                                <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="startEdit(branch)">Edit</button>
                                <button type="button" class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline" @click="remove(branch)">
                                    Remove
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create / edit -->
        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-lg overflow-auto p-6" @submit.prevent="save">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.name}` : 'New branch' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label">Name</label>
                        <input v-model="form.name" class="field-input">
                        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label">Address</label>
                        <textarea v-model="form.address" rows="2" class="field-input" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="field-label">Phone</label>
                            <input v-model="form.phone" class="field-input">
                        </div>
                        <div>
                            <label class="field-label">Sales phone</label>
                            <input v-model="form.sales_phone" class="field-input">
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Email</label>
                        <input v-model="form.email" type="email" class="field-input">
                        <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label">Map embed (optional)</label>
                        <textarea v-model="form.map_embed" rows="2" class="field-input" />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_pickup_location" type="checkbox" class="rounded">
                        Customers can collect from this branch
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save branch' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
