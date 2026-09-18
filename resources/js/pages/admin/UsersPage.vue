<script setup>
import { onMounted, ref } from 'vue';
import { admin } from '@/api';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import { useResourceCrud } from '@/support/useResourceCrud';

/**
 * Staff accounts.
 *
 * A branch manager is the `branch_manager` role plus a branch. That is why a
 * new location needs no new role: the previous system had one role string per
 * city and a duplicated route group behind each.
 *
 * Removing a user deactivates rather than deletes it server-side, so its
 * audit trail survives -- the action here is labelled to match.
 */
const auth = useAuthStore();
const reference = useReferenceStore();

const roles = ref([]);

const crud = useResourceCrud({
    list: () => admin.users().then((response) => response.data),
    create: (payload) => admin.createUser(payload),
    update: (id, payload) => admin.updateUser(id, payload),
    remove: (id) => admin.deleteUser(id),
    blank: { name: '', email: '', phone: '', password: '', branch_id: null, role: '', is_active: true },
    toForm: (user) => ({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        password: '',
        branch_id: user.branch?.id ?? null,
        role: user.roles?.[0] ?? '',
        is_active: user.is_active,
    }),
    savedMessage: (user, created) => created ? `${user.name} created.` : `${user.name} updated.`,
});

const { items: users, loading, saving, editing, showForm, notice, errors, form } = crud;

const canManage = () => auth.can('users.manage');
const isSelf = (user) => user.id === auth.user?.id;

/**
 * A blank password means "leave it as is" on edit; it is only required when
 * creating an account.
 */
function submit() {
    const payload = { ...form };

    if (payload.password === '') {
        delete payload.password;
    }

    return crud.save(payload);
}

onMounted(async () => {
    await Promise.all([crud.load(), admin.roles().then((list) => { roles.value = list; })]);
});
</script>

<template>
    <div>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Staff</h1>
                <p class="text-sm text-ink-400">
                    {{ roles.length }} roles, assigned by permission rather than by branch.
                </p>
            </div>

            <button v-if="canManage()" type="button" class="btn-primary" @click="crud.startCreate">
                Add staff
            </button>
        </header>

        <p v-if="notice" class="mb-4 rounded bg-emerald-50 p-3 text-sm text-emerald-700">{{ notice }}</p>

        <div v-if="loading" class="space-y-3">
            <div v-for="n in 5" :key="n" class="h-16 animate-pulse rounded-2xl bg-white" />
        </div>

        <div v-else class="card overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-400">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3">Branch</th>
                        <th class="px-4 py-3">Active</th>
                        <th class="px-4 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr v-for="user in users" :key="user.id">
                        <td class="px-4 py-3 font-semibold text-ink-900">{{ user.name }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-for="role in user.roles"
                                :key="role"
                                class="mr-1 rounded-full bg-ink-100 px-2 py-0.5 text-xs font-semibold text-ink-500"
                            >{{ role }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-500">{{ user.branch?.name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-ink-100 text-ink-400'"
                            >{{ user.is_active ? 'Yes' : 'No' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <template v-if="canManage()">
                                <button type="button" class="btn-ghost px-2 py-1 text-xs" @click="crud.startEdit(user)">
                                    Edit
                                </button>
                                <button
                                    v-if="user.is_active && !isSelf(user)"
                                    type="button"
                                    class="px-2 py-1 text-xs font-semibold text-red-600 hover:underline"
                                    @click="crud.remove(user)"
                                >
                                    Deactivate
                                </button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-ink-400">No staff accounts yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4">
            <form class="card max-h-full w-full max-w-lg overflow-auto p-6" @submit.prevent="submit">
                <h2 class="font-heading text-lg font-semibold text-ink-900">
                    {{ editing ? `Edit ${editing.name}` : 'New staff account' }}
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="field-label" for="user-name">Name</label>
                        <input id="user-name" v-model="form.name" class="field-input">
                        <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="user-email">Email</label>
                        <input id="user-email" v-model="form.email" type="email" class="field-input">
                        <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="user-phone">Phone</label>
                        <input id="user-phone" v-model="form.phone" class="field-input">
                        <span v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="user-password">
                            Password {{ editing ? '(leave empty to keep the current one)' : '' }}
                        </label>
                        <input id="user-password" v-model="form.password" type="password" class="field-input">
                        <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="user-role">Role</label>
                        <select id="user-role" v-model="form.role" class="field-input">
                            <option value="" disabled>Select a role</option>
                            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                        </select>
                        <span v-if="errors.role" class="field-error">{{ errors.role[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label" for="user-branch">Branch</label>
                        <select id="user-branch" v-model="form.branch_id" class="field-input">
                            <option :value="null">None (all branches)</option>
                            <option v-for="branch in reference.branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                        <span v-if="errors.branch_id" class="field-error">{{ errors.branch_id[0] }}</span>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink-600">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-ink-300">
                        Active
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn-secondary" @click="showForm = false">Cancel</button>
                    <button type="submit" class="btn-primary" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save staff account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
