<script setup>
import { onMounted, ref } from 'vue';
import { admin } from '@/api';

/**
 * Staff accounts.
 *
 * A branch manager is the `branch_manager` role plus a branch. That is why a
 * new location needs no new role: the previous system had one role string per
 * city and a duplicated route group behind each.
 */
const users = ref([]);
const roles = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const [list, roleList] = await Promise.all([admin.users(), admin.roles()]);
        users.value = list.data;
        roles.value = roleList;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <header class="mb-6">
            <h1 class="font-heading text-2xl font-semibold text-ink-900">Staff</h1>
            <p class="text-sm text-ink-400">
                {{ roles.length }} roles, assigned by permission rather than by branch.
            </p>
        </header>

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
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
