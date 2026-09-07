<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useCartStore } from '@/stores/cart';

const auth = useAuthStore();
const cart = useCartStore();
const router = useRouter();

const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const errors = reactive({});
const submitting = ref(false);

async function submit() {
    submitting.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await auth.register(form);
        await cart.load();
        await router.push({ name: 'home' });
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md px-4 py-16">
        <h1 class="font-display text-4xl text-ink-900">Create an account</h1>
        <p class="mt-1 text-sm text-ink-400">Optional &mdash; it just keeps your order history in one place.</p>

        <form class="card mt-8 space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label class="field-label" for="name">Name</label>
                <input id="name" v-model="form.name" class="field-input" autocomplete="name">
                <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
            </div>

            <div>
                <label class="field-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="field-input" autocomplete="email">
                <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
            </div>

            <div>
                <label class="field-label" for="phone">Mobile number (optional)</label>
                <div class="flex">
                    <span class="rounded-l border border-r-0 border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-400">+63</span>
                    <input id="phone" v-model="form.phone" class="field-input rounded-l-none" placeholder="9171234567" inputmode="numeric">
                </div>
                <span v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</span>
            </div>

            <div>
                <label class="field-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="field-input" autocomplete="new-password">
                <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
            </div>

            <div>
                <label class="field-label" for="confirm">Confirm password</label>
                <input id="confirm" v-model="form.password_confirmation" type="password" class="field-input" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary w-full" :disabled="submitting">
                {{ submitting ? 'Creating…' : 'Create account' }}
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-ink-400">
            Already registered?
            <RouterLink :to="{ name: 'login' }" class="font-semibold text-ink-700 hover:underline">Sign in</RouterLink>
        </p>
    </div>
</template>
