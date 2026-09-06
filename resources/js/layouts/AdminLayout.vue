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
    <div class="flex min-h-screen bg-slate-50">
        <aside
            class="flex flex-col border-r border-slate-200 bg-white transition-all"
            :class="collapsed ? 'w-16' : 'w-60'"
        >
            <div class="flex h-16 items-center gap-2 border-b border-slate-200 px-4">
                <span v-if="!collapsed" class="font-display text-2xl text-ink-700">Kompra</span>
                <button
                    type="button"
                    class="ml-auto rounded p-2 text-slate-500 hover:bg-slate-100"
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
                        class="px-3 pt-2 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400"
                    >
                        {{ section.label }}
                    </p>

                    <RouterLink
                        v-for="link in section.links"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="block rounded px-3 py-2 font-heading text-sm font-semibold text-slate-600 hover:bg-slate-100"
                        active-class="bg-brand-50 text-brand-600"
                        :title="link.label"
                    >
                        {{ collapsed ? link.label.charAt(0) : link.label }}
                    </RouterLink>
                </div>
            </nav>

            <div class="border-t border-slate-200 p-2">
                <RouterLink :to="{ name: 'home' }" class="block rounded px-3 py-2 text-sm text-slate-500 hover:bg-slate-100">
                    {{ collapsed ? '↩' : 'View storefront' }}
                </RouterLink>
            </div>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-6">
                <div>
                    <p class="font-heading text-sm font-semibold text-ink-700">{{ auth.user?.name }}</p>
                    <p class="text-xs text-slate-500">
                        {{ auth.user?.roles?.join(', ') }}
                        <span v-if="auth.branchName"> &middot; {{ auth.branchName }}</span>
                    </p>
                </div>

                <button type="button" class="btn-ghost ml-auto" @click="signOut">Sign out</button>
            </header>

            <main class="flex-1 overflow-auto p-6">
                <RouterView />
            </main>
        </div>
    </div>
</template>
