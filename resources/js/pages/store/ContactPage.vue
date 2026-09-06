<script setup>
import InquiryForm from '@/components/InquiryForm.vue';
import { useReferenceStore } from '@/stores/reference';

const reference = useReferenceStore();
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-700">Contact us</h1>
        <p class="mt-1 text-sm text-slate-500">
            Send us a general enquiry, or message a branch directly from the branches page.
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            <div class="card p-6">
                <!-- branch-id omitted: this is a general enquiry. -->
                <InquiryForm />
            </div>

            <div>
                <h2 class="font-heading text-xl font-bold text-ink-700">Where to find us</h2>
                <ul class="mt-4 space-y-4 text-sm">
                    <li v-for="branch in reference.pickupBranches" :key="branch.id" class="card p-4">
                        <p class="font-heading font-semibold text-ink-700">{{ branch.name }}</p>
                        <p v-if="branch.address" class="mt-1 text-slate-600">{{ branch.address }}</p>
                        <p v-if="branch.phone" class="text-slate-600">{{ branch.phone }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
