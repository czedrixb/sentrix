<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

const auth = useAuthStore();
const cart = useCartStore();
const router = useRouter();
const route = useRoute();

const form = reactive({ email: '', password: '', remember: false });
const errors = reactive({});
const submitting = ref(false);

async function submit() {
    submitting.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const user = await auth.login(form);

        // A guest cart is adopted server-side on sign-in; reload it here.
        await cart.load();

        const isStaff = (user.permissions?.length ?? 0) > 0;

        await router.push(route.query.redirect ?? { name: isStaff ? 'admin.dashboard' : 'home' });
    } catch (failure) {
        Object.assign(errors, failure.errors ?? { email: [failure.message] });
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-display text-4xl text-ink-700">Sign in</h1>
        <p class="mt-1 text-sm text-slate-500">One sign-in for customers and staff alike.</p>

        <form class="card mt-8 space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label class="field-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="field-input" autocomplete="email">
                <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
            </div>

            <div>
                <label class="field-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="field-input" autocomplete="current-password">
                <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="form.remember" type="checkbox" class="rounded">
                Keep me signed in
            </label>

            <button type="submit" class="btn-primary w-full" :disabled="submitting">
                {{ submitting ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-slate-500">
            No account?
            <RouterLink :to="{ name: 'register' }" class="font-semibold text-ink-700 hover:underline">Create one</RouterLink>
            &mdash; or just
            <RouterLink :to="{ name: 'shop' }" class="font-semibold text-ink-700 hover:underline">check out as a guest</RouterLink>.
        </p>
    </div>
</template>
