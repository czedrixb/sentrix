import { defineStore } from 'pinia';
import { reference as referenceApi } from '@/api';

/**
 * Branches, categories and brands, loaded once and shared.
 *
 * The storefront reads its branch list from here rather than from a hardcoded
 * array, which is what makes a newly created branch appear everywhere -- the
 * branch picker, the shop filter, the enquiry form -- with no code change.
 */
export const useReferenceStore = defineStore('reference', {
    state: () => ({
        branches: [],
        categories: [],
        brands: [],
        loaded: false,
        selectedBranchSlug: null,
    }),

    getters: {
        pickupBranches: (state) => state.branches.filter((branch) => branch.is_pickup_location),

        selectedBranch: (state) =>
            state.branches.find((branch) => branch.slug === state.selectedBranchSlug) ?? null,

        /**
         * Categories flattened for filter lists, parents and children alike.
         */
        flatCategories: (state) =>
            state.categories.flatMap((category) => [category, ...(category.children ?? [])]),
    },

    actions: {
        async load() {
            if (this.loaded) {
                return;
            }

            const [branches, categories, brands] = await Promise.all([
                referenceApi.branches(),
                referenceApi.categories(),
                referenceApi.brands(),
            ]);

            this.branches = branches;
            this.categories = categories;
            this.brands = brands;
            this.loaded = true;

            this.restoreBranch();
        },

        selectBranch(slug) {
            this.selectedBranchSlug = slug;

            try {
                window.localStorage.setItem('kompra.branch', slug ?? '');
            } catch {
                // Storage can be unavailable (private mode); the choice simply
                // does not persist between visits.
            }
        },

        /**
         * Restore the visitor's branch, falling back to the first pickup branch
         * so the shop is never empty on a first visit.
         */
        restoreBranch() {
            let stored = null;

            try {
                stored = window.localStorage.getItem('kompra.branch');
            } catch {
                stored = null;
            }

            const exists = this.branches.some((branch) => branch.slug === stored);

            this.selectedBranchSlug = exists ? stored : (this.pickupBranches[0]?.slug ?? null);
        },
    },
});
