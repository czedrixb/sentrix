import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const StoreLayout = () => import('@/layouts/StoreLayout.vue');
const AdminLayout = () => import('@/layouts/AdminLayout.vue');

const routes = [
    {
        path: '/',
        component: StoreLayout,
        children: [
            { path: '', name: 'home', component: () => import('@/pages/store/HomePage.vue') },
            { path: 'about', name: 'about', component: () => import('@/pages/store/AboutPage.vue') },
            { path: 'branches', name: 'branches', component: () => import('@/pages/store/BranchesPage.vue') },
            { path: 'shop', name: 'shop', component: () => import('@/pages/store/ShopPage.vue') },
            { path: 'products/:slug', name: 'product', component: () => import('@/pages/store/ProductPage.vue') },
            { path: 'cart', name: 'cart', component: () => import('@/pages/store/CartPage.vue') },
            { path: 'checkout', name: 'checkout', component: () => import('@/pages/store/CheckoutPage.vue') },
            { path: 'orders/:number', name: 'order', component: () => import('@/pages/store/OrderPage.vue') },
            { path: 'track', name: 'track', component: () => import('@/pages/store/TrackOrderPage.vue') },
            { path: 'news', name: 'news', component: () => import('@/pages/store/NewsPage.vue') },
            { path: 'news/:slug', name: 'post', component: () => import('@/pages/store/PostPage.vue') },
            { path: 'careers', name: 'careers', component: () => import('@/pages/store/CareersPage.vue') },
            { path: 'gallery', name: 'gallery', component: () => import('@/pages/store/GalleryPage.vue') },
            { path: 'contact', name: 'contact', component: () => import('@/pages/store/ContactPage.vue') },
            { path: 'login', name: 'login', component: () => import('@/pages/store/LoginPage.vue'), meta: { guestOnly: true } },
            { path: 'register', name: 'register', component: () => import('@/pages/store/RegisterPage.vue'), meta: { guestOnly: true } },
            {
                path: 'account/orders',
                name: 'account.orders',
                component: () => import('@/pages/store/AccountOrdersPage.vue'),
                meta: { requiresAuth: true },
            },
        ],
    },

    /*
     | The admin is one lazily-loaded branch of the same app, so PrimeVue and
     | the management screens never ship to a shopper.
     */
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAuth: true, requiresStaff: true },
        children: [
            { path: '', name: 'admin.dashboard', component: () => import('@/pages/admin/DashboardPage.vue'), meta: { permission: 'dashboard.view' } },
            { path: 'orders', name: 'admin.orders', component: () => import('@/pages/admin/OrdersPage.vue'), meta: { permission: 'orders.view' } },
            { path: 'products', name: 'admin.products', component: () => import('@/pages/admin/ProductsPage.vue'), meta: { permission: 'products.view' } },
            { path: 'categories', name: 'admin.categories', component: () => import('@/pages/admin/CategoriesPage.vue'), meta: { permission: 'products.view' } },
            { path: 'brands', name: 'admin.brands', component: () => import('@/pages/admin/BrandsPage.vue'), meta: { permission: 'products.view' } },
            { path: 'branches', name: 'admin.branches', component: () => import('@/pages/admin/BranchesPage.vue') },
            { path: 'inquiries', name: 'admin.inquiries', component: () => import('@/pages/admin/InquiriesPage.vue'), meta: { permission: 'inquiries.view' } },
            { path: 'vouchers', name: 'admin.vouchers', component: () => import('@/pages/admin/VouchersPage.vue'), meta: { permission: 'vouchers.view' } },
            { path: 'posts', name: 'admin.posts', component: () => import('@/pages/admin/PostsPage.vue'), meta: { permission: 'content.view' } },
            { path: 'banners', name: 'admin.banners', component: () => import('@/pages/admin/BannersPage.vue'), meta: { permission: 'content.view' } },
            { path: 'gallery', name: 'admin.gallery', component: () => import('@/pages/admin/GalleryPage.vue'), meta: { permission: 'content.view' } },
            { path: 'careers', name: 'admin.careers', component: () => import('@/pages/admin/CareersPage.vue'), meta: { permission: 'careers.manage' } },
            { path: 'users', name: 'admin.users', component: () => import('@/pages/admin/UsersPage.vue'), meta: { permission: 'users.manage' } },
        ],
    },

    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/NotFoundPage.vue') },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (to, from, saved) => saved ?? { top: 0 },
});

/**
 * Route guards read the permission list the API returned. Authorisation is
 * still enforced server-side on every request; this only decides what to show.
 */
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    await auth.resolve();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: auth.isStaff ? 'admin.dashboard' : 'home' };
    }

    if (to.meta.requiresStaff && !auth.isStaff) {
        return { name: 'home' };
    }

    if (to.meta.permission && !auth.can(to.meta.permission)) {
        return { name: 'admin.dashboard' };
    }

    return true;
});

export default router;
