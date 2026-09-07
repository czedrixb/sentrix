<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
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

/**
 * The header sits flat on the page until it is scrolled past, then picks up a
 * border, a blur and a shadow. Same passive-listener shape as ScrollToTop.
 */
const scrolled = ref(false);

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

/**
 * `exact` marks a link whose path is a prefix of every other route. Without it
 * RouterLink's inclusive match keeps Home highlighted on every page, which was
 * invisible while the active state was a colour change and obvious now that it
 * is a filled pill. News deliberately stays inclusive so /news/:slug keeps it lit.
 */
const nav = [
    { name: 'home', label: 'Home', exact: true },
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
        <header
            class="sticky top-0 z-40 transition-[background-color,border-color,box-shadow,backdrop-filter] duration-300 ease-soft"
            :class="scrolled
                ? 'border-b border-ink-200/70 bg-white/85 shadow-[0_1px_20px_rgba(14,15,17,0.05)] backdrop-blur-md'
                : 'border-b border-transparent bg-canvas/60 backdrop-blur-sm'"
        >
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4">
                <RouterLink
                    :to="{ name: 'home' }"
                    class="group flex items-center gap-2.5 transition-opacity duration-300 ease-soft hover:opacity-80"
                >
                    <!-- Shield mark: the brand's only piece of iconography. -->
                    <svg class="size-7 text-brand-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M12 2.5 4.5 5.6v5.9c0 4.7 3.1 8.4 7.5 10 4.4-1.6 7.5-5.3 7.5-10V5.6L12 2.5Z"
                            stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"
                        />
                        <circle cx="12" cy="11" r="2.6" stroke="currentColor" stroke-width="1.6" />
                    </svg>
                    <span class="font-display text-2xl font-semibold tracking-tight text-ink-900">Sentrix</span>
                </RouterLink>

                <nav class="ml-6 hidden items-center gap-0.5 lg:flex">
                    <RouterLink
                        v-for="link in nav"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="rounded-full px-3.5 py-2 font-heading text-sm text-ink-500 transition-colors duration-300 ease-soft hover:bg-ink-100 hover:text-ink-900"
                        :active-class="link.exact ? '' : 'bg-ink-100 text-ink-900'"
                        :exact-active-class="link.exact ? 'bg-ink-100 text-ink-900' : ''"
                    >
                        {{ link.label }}
                    </RouterLink>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <BranchPicker class="hidden sm:block" />

                    <RouterLink
                        :to="{ name: 'cart' }"
                        class="relative rounded-full p-2.5 text-ink-700 transition-colors duration-300 ease-soft hover:bg-ink-100"
                        aria-label="Cart"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.5l2.1 12.6a1.5 1.5 0 0 0 1.48 1.25h9.34a1.5 1.5 0 0 0 1.47-1.2L19.5 6.75H5.1" />
                            <circle cx="9" cy="20" r="1.25" />
                            <circle cx="17" cy="20" r="1.25" />
                        </svg>
                        <span
                            v-if="cart.itemCount > 0"
                            class="absolute right-0 top-0 grid size-5 place-items-center rounded-full bg-brand-600 text-[11px] font-semibold text-white"
                        >{{ cart.itemCount }}</span>
                    </RouterLink>

                    <template v-if="auth.isAuthenticated">
                        <RouterLink v-if="auth.isStaff" :to="{ name: 'admin.dashboard' }" class="btn-secondary hidden px-5 py-2.5 sm:inline-flex">
                            Admin
                        </RouterLink>
                        <RouterLink v-else :to="{ name: 'account.orders' }" class="btn-ghost hidden px-5 py-2.5 sm:inline-flex">
                            My orders
                        </RouterLink>
                        <button type="button" class="btn-ghost px-5 py-2.5" @click="signOut">Sign out</button>
                    </template>
                    <RouterLink v-else :to="{ name: 'login' }" class="btn-ghost px-5 py-2.5">Sign in</RouterLink>

                    <button
                        type="button"
                        class="rounded-full p-2.5 text-ink-700 transition-colors duration-300 ease-soft hover:bg-ink-100 lg:hidden"
                        aria-label="Menu"
                        :aria-expanded="mobileOpen"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <Transition name="drawer">
                <nav v-if="mobileOpen" class="overflow-hidden border-t border-ink-200/70 bg-white px-4 py-2 lg:hidden">
                    <BranchPicker class="mb-2 sm:hidden" />
                    <RouterLink
                        v-for="link in nav"
                        :key="link.name"
                        :to="{ name: link.name }"
                        class="block rounded-xl px-3 py-2.5 font-heading text-sm text-ink-700 transition-colors duration-200 ease-soft hover:bg-ink-100"
                        :active-class="link.exact ? '' : 'bg-ink-100 text-ink-900'"
                        :exact-active-class="link.exact ? 'bg-ink-100 text-ink-900' : ''"
                        @click="mobileOpen = false"
                    >
                        {{ link.label }}
                    </RouterLink>
                </nav>
            </Transition>
        </header>

        <main class="flex-1">
            <RouterView v-slot="{ Component }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" />
                </Transition>
            </RouterView>
        </main>

        <footer class="mt-20 bg-ink-900 text-ink-300">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <svg class="size-6 text-brand-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M12 2.5 4.5 5.6v5.9c0 4.7 3.1 8.4 7.5 10 4.4-1.6 7.5-5.3 7.5-10V5.6L12 2.5Z"
                                stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"
                            />
                            <circle cx="12" cy="11" r="2.6" stroke="currentColor" stroke-width="1.6" />
                        </svg>
                        <p class="font-display text-2xl font-semibold tracking-tight text-white">Sentrix</p>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-400">
                        CCTV, alarms and access control &mdash; stocked, installed and serviced across our branches
                        nationwide.
                    </p>
                </div>

                <div>
                    <p class="font-heading text-sm font-medium text-white">Explore</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li v-for="link in nav.slice(1)" :key="link.name">
                            <RouterLink
                                :to="{ name: link.name }"
                                class="transition-colors duration-300 ease-soft hover:text-brand-300"
                            >{{ link.label }}</RouterLink>
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="font-heading text-sm font-medium text-white">Branches</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li v-for="branch in reference.branches.slice(0, 6)" :key="branch.id">
                            {{ branch.name }}
                        </li>
                    </ul>
                </div>

                <div>
                    <p class="font-heading text-sm font-medium text-white">Orders</p>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li>
                            <RouterLink :to="{ name: 'track' }" class="transition-colors duration-300 ease-soft hover:text-brand-300">
                                Track an order
                            </RouterLink>
                        </li>
                        <li>
                            <RouterLink :to="{ name: 'contact' }" class="transition-colors duration-300 ease-soft hover:text-brand-300">
                                Get in touch
                            </RouterLink>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10 py-5 text-center text-xs text-ink-400">
                &copy; {{ new Date().getFullYear() }} Sentrix. All rights reserved.
            </div>
        </footer>

        <ScrollToTop />
    </div>
</template>

<style scoped>
/*
 | The mobile navigation used to pop in on a bare v-if. Animating the height
 | rather than only the opacity keeps the header from jumping under it.
 */
.drawer-enter-active,
.drawer-leave-active {
    transition: max-height 0.3s var(--ease-soft), opacity 0.3s var(--ease-soft);
}

.drawer-enter-from,
.drawer-leave-to {
    max-height: 0;
    opacity: 0;
}

.drawer-enter-to,
.drawer-leave-from {
    max-height: 32rem;
    opacity: 1;
}
</style>
