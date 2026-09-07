<script setup>
import { reactive, ref } from 'vue';
import { content } from '@/api';

/**
 * One enquiry form for every branch, and for general enquiries.
 *
 * Pass a branchId or leave it null. The previous system needed a separate
 * form, route, controller, model and table per city -- eight of each.
 */
const props = defineProps({
    branchId: { type: Number, default: null },
});

const form = reactive({ name: '', email: '', phone: '', message: '' });
const errors = reactive({});
const sending = ref(false);
const sent = ref(false);

async function submit() {
    sending.value = true;
    sent.value = false;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await content.submitInquiry({ ...form, branch_id: props.branchId });

        sent.value = true;
        Object.assign(form, { name: '', email: '', phone: '', message: '' });
    } catch (failure) {
        Object.assign(errors, failure.errors ?? {});
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <p v-if="sent" class="rounded bg-emerald-50 p-3 text-sm text-emerald-700">
            Thanks &mdash; your message is on its way.
        </p>

        <div>
            <label class="field-label">Name</label>
            <input v-model="form.name" class="field-input" autocomplete="name">
            <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
        </div>

        <div>
            <label class="field-label">Email</label>
            <input v-model="form.email" type="email" class="field-input" autocomplete="email">
            <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
        </div>

        <div>
            <label class="field-label">Mobile number</label>
            <div class="flex">
                <span class="rounded-l border border-r-0 border-ink-200 bg-ink-50 px-3 py-2 text-sm text-ink-400">+63</span>
                <input v-model="form.phone" class="field-input rounded-l-none" placeholder="9171234567" inputmode="numeric">
            </div>
            <span v-if="errors.phone" class="field-error">{{ errors.phone[0] }}</span>
        </div>

        <div>
            <label class="field-label">Message</label>
            <textarea v-model="form.message" rows="4" class="field-input" />
            <span v-if="errors.message" class="field-error">{{ errors.message[0] }}</span>
        </div>

        <button type="submit" class="btn-primary" :disabled="sending">
            {{ sending ? 'Sending…' : 'Send message' }}
        </button>
    </form>
</template>
