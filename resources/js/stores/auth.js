import { defineStore } from 'pinia';
import { auth as authApi } from '@/api';

/**
 * The signed-in user, with the permissions the server granted.
 *
 * The SPA decides what to render from this list. There is no equivalent of the
 * previous system's fourteen-branch role-to-URL redirect switch, and no role
 * name is hardcoded into a route.
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        resolved: false,
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,
        isStaff: (state) => (state.user?.permissions?.length ?? 0) > 0,
        branchId: (state) => state.user?.branch?.id ?? null,
        branchName: (state) => state.user?.branch?.name ?? null,
    },

    actions: {
        /**
         * Whether the user holds a permission. Unknown permissions are false,
         * so a missing grant fails closed.
         */
        can(permission) {
            return this.user?.permissions?.includes(permission) ?? false;
        },

        canAny(permissions) {
            return permissions.some((permission) => this.can(permission));
        },

        hasRole(role) {
            return this.user?.roles?.includes(role) ?? false;
        },

        /**
         * Resolve the session once per page load.
         */
        async resolve() {
            if (this.resolved) {
                return this.user;
            }

            try {
                this.user = await authApi.me();
            } catch {
                this.user = null;
            } finally {
                this.resolved = true;
            }

            return this.user;
        },

        async login(credentials) {
            this.user = await authApi.login(credentials);
            this.resolved = true;

            return this.user;
        },

        async register(payload) {
            this.user = await authApi.register(payload);
            this.resolved = true;

            return this.user;
        },

        async logout() {
            await authApi.logout();

            this.user = null;
        },
    },
});
