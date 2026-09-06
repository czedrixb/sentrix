<script setup>
import { ref } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useCartStore } from '@/stores/cart';
import { useAuthStore } from '@/stores/auth';
import { useReferenceStore } from '@/stores/reference';
import BranchPicker from '@/components/BranchPicker.vue';
import ScrollToTop from '@/components/ScrollToTop.vue';

const cart = useCartStore();
const auth = useAuthStore();
const reference = useReferenceStore();
const router = useRouter();

const mobileOpen = ref(false);

const nav = [
    { name: 'home', label: 'Home' },
    { name: 'shop', label: 'Shop' },
    { name: 'branches', label: 'Branches' },
    { name: 'news', label: 'News' },
    { name: 'careers', label: 'Careers' },
    { name: 'gallery', label: 'Gallery' },
    { name: 'about', label: 'About' },
    { name: 'contact', label: 'Contact' },
];

async function signOut() {
    await auth.logout();
    await router.push({ name: 'home' });
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
                <RouterLink :to="{ name: 'home' }" class="font-display text-3xl tracking-wide text-ink-700">
                    Kompra
                </RouterLink>

                <nav class="ml-6 hidden items-center gap-1 lg:flex">
                    <RouterLink
                        v-for="link in nav"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="rounded px-3 py-2 font-heading text-sm font-semibold text-slate-600 hover:text-brand-500"
                        active-class="text-ink-700"
                    >
                        {{ link.label }}
                    </RouterLink>
                </nav>

                <div class="ml-auto flex items-center gap-3">
                    <BranchPicker class="hidden sm:block" />

                    <RouterLink
                        :to="{ name: 'cart' }"
                        class="relative rounded p-2 text-ink-700 hover:bg-slate-100"
                        aria-label="Cart"
                    >
                        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l2.1 12.6a1.5 1.5 0 0 0 1.48 1.25h9.34a1.5 1.5 0 0 0 1.47-1.2L19.5 6.75H5.1" />
                            <circle cx="9" cy="20" r="1.25" />
                            <circle cx="17" cy="20" r="1.25" />
                        </svg>
                        <span
                            v-if="cart.itemCount > 0"
                            class="absolute -right-0.5 -top-0.5 grid size-5 place-items-center rounded-full bg-brand-500 text-[11px] font-bold text-white"
                        >{{ cart.itemCount }}</span>
                    </RouterLink>

                    <template v-if="auth.isAuthenticated">
                        <RouterLink v-if="auth.isStaff" :to="{ name: 'admin.dashboard' }" class="btn-secondary hidden sm:inline-flex">
                            Admin
                        </RouterLink>
                        <RouterLink v-else :to="{ name: 'account.orders' }" class="btn-ghost hidden sm:inline-flex">
                            My orders
                        </RouterLink>
                        <button type="button" class="btn-ghost" @click="signOut">Sign out</button>
                    </template>
                    <RouterLink v-else :to="{ name: 'login' }" class="btn-ghost">Sign in</RouterLink>

                    <button
                        type="button"
                        class="rounded p-2 text-ink-700 hover:bg-slate-100 lg:hidden"
                        aria-label="Menu"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <nav v-if="mobileOpen" class="border-t border-slate-200 px-4 py-2 lg:hidden">
                <BranchPicker class="mb-2 sm:hidden" />
                <RouterLink
                    v-for="link in nav"
                    :key="link.name"
                    :to="{ name: link.name }"
                    class="block rounded px-3 py-2 font-heading text-sm font-semibold text-slate-700"
                    @click="mobileOpen = false"
                >
                    {{ link.label }}
                </RouterLink>
            </nav>
        </header>

        <main class="flex-1">
            <RouterView />
        </main>

        <footer class="mt-16 bg-ink-700 text-ink-100">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="font-display text-3xl text-white">Kompra</p>
                    <p class="mt-3 text-sm text-ink-200">
                        Equipment, consumables and supplies, stocked across our branches nationwide.
                    </p>
                </div>

                <div>
                    <p class="font-heading font-semibold text-white">Explore</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="link in nav.slice(1)" :key="link.name">
                            <RouterLink :to="{ name: link.name }" class="hover:text-brand-300">{{ link.label }}</RouterLink>
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="font-heading font-semibold text-white">Branches</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="branch in reference.branches.slice(0, 6)" :key="branch.id">
                            {{ branch.name }}
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="font-heading font-semibold text-white">Orders</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><RouterLink :to="{ name: 'track' }" class="hover:text-brand-300">Track an order</RouterLink></li>
                        <li><RouterLink :to="{ name: 'contact' }" class="hover:text-brand-300">Get in touch</RouterLink></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-ink-600 py-4 text-center text-xs text-ink-200">
                &copy; {{ new Date().getFullYear() }} Kompra. All rights reserved.
            </div>
        </footer>

        <ScrollToTop />
    </div>
</template>
