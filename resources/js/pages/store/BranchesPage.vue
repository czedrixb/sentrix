<script setup>
import { computed, ref } from 'vue';
import { useReferenceStore } from '@/stores/reference';
import InquiryForm from '@/components/InquiryForm.vue';

/**
 * Branch directory.
 *
 * One page, one component, driven by the branches endpoint. The previous site
 * rendered a separate hardcoded form per city, each posting to its own endpoint
 * chosen by a name-matching map that had already drifted out of sync.
 */
const reference = useReferenceStore();

const activeId = ref(null);

const branches = computed(() => reference.branches.filter((branch) => branch.is_pickup_location));

const active = computed(
    () => branches.value.find((branch) => branch.id === activeId.value) ?? branches.value[0] ?? null,
);
</script>

<template>
    <div class="mx-auto max-w-7xl px-4 py-10">
        <h1 class="font-display text-4xl text-ink-700">Our branches</h1>
        <p class="mt-1 text-sm text-slate-500">Visit us, or send the branch a message directly.</p>

        <div class="mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">
            <nav class="space-y-1">
                <button
                    v-for="branch in branches"
                    :key="branch.id"
                    type="button"
                    class="w-full rounded px-4 py-3 text-left font-heading text-sm font-semibold transition"
                    :class="active?.id === branch.id ? 'bg-brand-500 text-white' : 'bg-slate-50 text-ink-700 hover:bg-slate-100'"
                    @click="activeId = branch.id"
                >
                    {{ branch.name }}
                </button>
            </nav>

            <section v-if="active" class="grid gap-8 lg:grid-cols-2">
                <div>
                    <h2 class="font-heading text-2xl font-bold text-ink-700">{{ active.name }}</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div v-if="active.address">
                            <dt class="font-semibold text-ink-700">Address</dt>
                            <dd class="text-slate-600">{{ active.address }}</dd>
                        </div>
                        <div v-if="active.phone">
                            <dt class="font-semibold text-ink-700">Phone</dt>
                            <dd class="text-slate-600">{{ active.phone }}</dd>
                        </div>
                        <div v-if="active.sales_phone">
                            <dt class="font-semibold text-ink-700">Sales</dt>
                            <dd class="text-slate-600">{{ active.sales_phone }}</dd>
                        </div>
                        <div v-if="active.email">
                            <dt class="font-semibold text-ink-700">Email</dt>
                            <dd class="text-slate-600">{{ active.email }}</dd>
                        </div>
                    </dl>

                    <!-- Sanitised server-side before storage. -->
                    <div v-if="active.map_embed" class="prose-cms mt-6" v-html="active.map_embed" />
                </div>

                <div class="card p-5">
                    <h3 class="font-heading font-bold text-ink-700">Message this branch</h3>
                    <InquiryForm :key="active.id" :branch-id="active.id" class="mt-4" />
                </div>
            </section>
        </div>
    </div>
</template>
