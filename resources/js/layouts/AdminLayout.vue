<script setup>
import { computed, ref } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/**
 * Admin shell.
 *
 * The navigation is filtered by the permission list the API returned, so one
 * layout serves every role. The previous system had roughly two hundred inline
 * role comparisons in its sidebar template and a twelve-case PHP function
 * defined inside the Blade view to work out where "log out" should point.
 */
const auth = useAuthStore();
const router = useRouter();

const collapsed = ref(false);

/**
 * Grouped so thirteen modules stay readable. A section disappears entirely once
 * the permission filter empties it.
 */
const sections = computed(() =>
    [
        {
            label: null,
            links: [{ name: 'admin.dashboard', label: 'Dashboard', permission: 'dashboard.view' }],
        },
        {
            label: 'Selling',
            links: [
                { name: 'admin.orders', label: 'Orders', permission: 'orders.view' },
                { name: 'admin.vouchers', label: 'Vouchers', permission: 'vouchers.view' },
                { name: 'admin.inquiries', label: 'Enquiries', permission: 'inquiries.view' },
            ],
        },
        {
            label: 'Catalogue',
            links: [
                { name: 'admin.products', label: 'Products', permission: 'products.view' },
                { name: 'admin.categories', label: 'Categories', permission: 'products.view' },
                { name: 'admin.brands', label: 'Brands', permission: 'products.view' },
                { name: 'admin.branches', label: 'Branches', permission: null },
            ],
        },
        {
            label: 'Content',
            links: [
                { name: 'admin.posts', label: 'News', permission: 'content.view' },
                { name: 'admin.banners', label: 'Banners', permission: 'content.view' },
                { name: 'admin.gallery', label: 'Gallery', permission: 'content.view' },
                { name: 'admin.careers', label: 'Careers', permission: 'careers.manage' },
            ],
        },
        {
            label: 'People',
            links: [{ name: 'admin.users', label: 'Staff', permission: 'users.manage' }],
        },
    ]
        .map((section) => ({
            ...section,
            links: section.links.filter((link) => link.permission === null || auth.can(link.permission)),
        }))
        .filter((section) => section.links.length > 0),
);

async function signOut() {
    await auth.logout();
    await router.push({ name: 'home' });
}
</script>

<template>
    <div class="flex min-h-screen bg-ink-50">
        <aside
            class="flex flex-col border-r border-ink-200 bg-white transition-[width] duration-300 ease-soft"
            :class="collapsed ? 'w-16' : 'w-60'"
        >
            <div class="flex h-16 items-center gap-2 border-b border-ink-200 px-4">
                <svg class="size-6 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M12 2.5 4.5 5.6v5.9c0 4.7 3.1 8.4 7.5 10 4.4-1.6 7.5-5.3 7.5-10V5.6L12 2.5Z"
                        stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"
                    />
                    <circle cx="12" cy="11" r="2.6" stroke="currentColor" stroke-width="1.6" />
                </svg>
                <span v-if="!collapsed" class="font-display text-xl font-semibold tracking-tight text-ink-900">Sentrix</span>
                <button
                    type="button"
                    class="ml-auto rounded-lg p-2 text-ink-400 transition-colors duration-300 ease-soft hover:bg-ink-100"
                    aria-label="Toggle navigation"
                    @click="collapsed = !collapsed"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-4 overflow-y-auto p-2">
                <div v-for="section in sections" :key="section.label ?? 'root'" class="space-y-1">
                    <p
                        v-if="section.label && !collapsed"
                        class="px-3 pt-2 text-[0.65rem] font-semibold uppercase tracking-wider text-ink-400"
                    >
                        {{ section.label }}
                    </p>

                    <RouterLink
                        v-for="link in section.links"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="block rounded-xl px-3 py-2 font-heading text-sm font-medium text-ink-500 transition-colors duration-300 ease-soft hover:bg-ink-100 hover:text-ink-900"
                        active-class="bg-brand-50 text-brand-700"
                        :title="link.label"
                    >
                        {{ collapsed ? link.label.charAt(0) : link.label }}
                    </RouterLink>
                </div>
            </nav>

            <div class="border-t border-ink-200 p-2">
                <RouterLink :to="{ name: 'home' }" class="block rounded-xl px-3 py-2 text-sm text-ink-400 transition-colors duration-300 ease-soft hover:bg-ink-100">
                    {{ collapsed ? '↩' : 'View storefront' }}
                </RouterLink>
            </div>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex h-16 items-center gap-4 border-b border-ink-200 bg-white px-6">
                <div>
                    <p class="font-heading text-sm font-semibold text-ink-900">{{ auth.user?.name }}</p>
                    <p class="text-xs text-ink-400">
                        {{ auth.user?.roles?.join(', ') }}
                        <span v-if="auth.branchName"> &middot; {{ auth.branchName }}</span>
                    </p>
                </div>

                <button type="button" class="btn-ghost ml-auto" @click="signOut">Sign out</button>
            </header>

            <main class="flex-1 overflow-auto p-6">
                <RouterView v-slot="{ Component }">
                    <Transition name="page" mode="out-in">
                        <component :is="Component" />
                    </Transition>
                </RouterView>
            </main>
        </div>
    </div>
</template>
