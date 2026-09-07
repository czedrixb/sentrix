<script setup>
import InquiryForm from '@/components/InquiryForm.vue';
import { useReferenceStore } from '@/stores/reference';

const reference = useReferenceStore();
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <p class="eyebrow">Get In Touch</p>
        <h1 class="mt-3 text-4xl font-normal text-ink-900">Contact us</h1>
        <p class="mt-1 text-sm text-ink-400">
            Ask for a quotation, book a site survey, or message a branch directly from the branches page.
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            <div class="card p-6">
                <!-- branch-id omitted: this is a general enquiry. -->
                <InquiryForm />
            </div>

            <div>
                <h2 class="font-heading text-xl font-semibold text-ink-900">Where to find us</h2>
                <ul class="mt-4 space-y-4 text-sm">
                    <li v-for="branch in reference.pickupBranches" :key="branch.id" class="card p-4">
                        <p class="font-heading font-semibold text-ink-900">{{ branch.name }}</p>
                        <p v-if="branch.address" class="mt-1 text-ink-500">{{ branch.address }}</p>
                        <p v-if="branch.phone" class="text-ink-500">{{ branch.phone }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
