import { defineStore } from 'pinia';
import { cart as cartApi } from '@/api';

/**
 * Cart state.
 *
 * Every figure here comes from the server. The store never adds up a total of
 * its own -- displaying one number while the server charges another is exactly
 * how the previous system ended up letting the browser set the price.
 */
export const useCartStore = defineStore('cart', {
    state: () => ({
        token: null,
        branch: null,
        items: [],
        totals: { subtotal: '0.00', discount_total: '0.00', delivery_fee: '0.00', grand_total: '0.00' },
        voucher: null,
        voucherRejectionReason: null,
        fulfillmentType: null,
        requiresDelivery: false,
        itemCount: 0,
        loading: false,
        notice: null,
    }),

    getters: {
        isEmpty: (state) => state.items.length === 0,
        lockedBranchId: (state) => state.branch?.id ?? null,
    },

    actions: {
        apply(payload) {
            const data = payload.data ?? payload;

            this.token = data.token;
            this.branch = data.branch ?? null;
            this.items = data.items ?? [];
            this.totals = data.totals;
            this.voucher = data.voucher ?? null;
            this.voucherRejectionReason = data.voucher_rejection_reason ?? null;
            this.fulfillmentType = data.fulfillment_type ?? null;
            this.requiresDelivery = data.requires_delivery ?? false;
            this.itemCount = data.item_count ?? 0;
            this.notice = payload.notice ?? null;
        },

        async load() {
            this.loading = true;

            try {
                this.apply(await cartApi.show());
            } finally {
                this.loading = false;
            }
        },

        async addItem(productId, branchId, quantity = 1) {
            this.apply(await cartApi.addItem({ product_id: productId, branch_id: branchId, quantity }));
        },

        async updateItem(itemId, quantity) {
            this.apply(await cartApi.updateItem(itemId, quantity));
        },

        async removeItem(itemId) {
            this.apply(await cartApi.removeItem(itemId));
        },

        async clear() {
            this.apply(await cartApi.clear());
        },

        async applyVoucher(code) {
            this.apply(await cartApi.applyVoucher(code));
        },

        async setFulfillment(type) {
            this.apply(await cartApi.setFulfillment(type));
        },
    },
});
