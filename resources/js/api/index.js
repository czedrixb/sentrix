import client from './client';

/**
 * Build a multipart body for the endpoints that accept an upload.
 *
 * Multipart carries no types, so booleans become 1/0 and nulls are dropped
 * rather than arriving as the string "null". Arrays are sent as `key[]`, which
 * is the shape `images.*` validation expects.
 */
export function toFormData(payload) {
    const body = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        if (typeof value === 'boolean') {
            body.append(key, value ? '1' : '0');

            return;
        }

        if (Array.isArray(value) || value instanceof FileList) {
            Array.from(value).forEach((entry) => body.append(`${key}[]`, entry));

            return;
        }

        body.append(key, value);
    });

    return body;
}

/**
 * Every endpoint the SPA talks to, in one place.
 *
 * Nothing here is branch-specific. Branches arrive from `reference.branches()`
 * and are passed through as ids, so a branch added in the admin shows up across
 * the storefront with no frontend change.
 */
export const reference = {
    branches: () => client.get('/branches').then((r) => r.data.data),
    categories: () => client.get('/categories').then((r) => r.data.data),
    brands: () => client.get('/brands').then((r) => r.data.data),
    banners: () => client.get('/banners').then((r) => r.data.data),
    gallery: () => client.get('/gallery').then((r) => r.data.data),
};

export const catalog = {
    products: (params = {}) => client.get('/products', { params }).then((r) => r.data),
    product: (slug, params = {}) => client.get(`/products/${slug}`, { params }).then((r) => r.data.data),
    related: (slug, params = {}) => client.get(`/products/${slug}/related`, { params }).then((r) => r.data.data),
};

export const content = {
    posts: (params = {}) => client.get('/posts', { params }).then((r) => r.data),
    post: (slug) => client.get(`/posts/${slug}`).then((r) => r.data.data),
    careers: (params = {}) => client.get('/careers', { params }).then((r) => r.data),
    submitInquiry: (payload) => client.post('/inquiries', payload).then((r) => r.data),
};

export const cart = {
    show: () => client.get('/cart').then((r) => r.data),
    addItem: (payload) => client.post('/cart/items', payload).then((r) => r.data),
    updateItem: (id, quantity) => client.patch(`/cart/items/${id}`, { quantity }).then((r) => r.data),
    removeItem: (id) => client.delete(`/cart/items/${id}`).then((r) => r.data),
    clear: () => client.delete('/cart').then((r) => r.data),
    applyVoucher: (code) => client.post('/cart/voucher', { code }).then((r) => r.data),
    setFulfillment: (type) => client.post('/cart/fulfillment', { fulfillment_type: type }).then((r) => r.data),
};

export const orders = {
    place: (payload) => client.post('/orders', payload).then((r) => r.data),
    mine: (params = {}) => client.get('/orders', { params }).then((r) => r.data),
    lookup: (payload) => client.post('/orders/lookup', payload).then((r) => r.data.data),
};

export const auth = {
    me: () => client.get('/me').then((r) => r.data.user),
    login: (payload) => client.post('/login', payload).then((r) => r.data.user),
    register: (payload) => client.post('/register', payload).then((r) => r.data.user),
    logout: () => client.post('/logout').then((r) => r.data),
};

export const admin = {
    dashboard: () => client.get('/admin/dashboard').then((r) => r.data.data),
    lowStock: (params = {}) => client.get('/admin/dashboard/low-stock', { params }).then((r) => r.data),
    revenueSeries: (range) =>
        client.get('/admin/dashboard/revenue', { params: { range } }).then((r) => r.data.data),

    orders: (params = {}) => client.get('/admin/orders', { params }).then((r) => r.data),
    order: (number) => client.get(`/admin/orders/${number}`).then((r) => r.data.data),
    setOrderStatus: (number, status) =>
        client.patch(`/admin/orders/${number}/status`, { status }).then((r) => r.data.data),
    setOrderPayment: (number, paymentStatus) =>
        client.patch(`/admin/orders/${number}/payment`, { payment_status: paymentStatus }).then((r) => r.data.data),
    archiveOrder: (number) => client.post(`/admin/orders/${number}/archive`).then((r) => r.data.data),
    restoreOrder: (number) => client.delete(`/admin/orders/${number}/archive`).then((r) => r.data.data),

    products: (params = {}) => client.get('/admin/products', { params }).then((r) => r.data),
    product: (slug) => client.get(`/admin/products/${slug}`).then((r) => r.data.data),
    createProduct: (formData) => client.post('/admin/products', formData).then((r) => r.data.data),
    updateProduct: (slug, formData) => client.post(`/admin/products/${slug}`, formData).then((r) => r.data.data),
    archiveProduct: (slug) => client.post(`/admin/products/${slug}/archive`).then((r) => r.data.data),
    setStock: (slug, payload) => client.patch(`/admin/products/${slug}/stock`, payload).then((r) => r.data.data),

    branches: (params = {}) => client.get('/admin/branches', { params }).then((r) => r.data.data),
    createBranch: (payload) => client.post('/admin/branches', payload).then((r) => r.data.data),
    updateBranch: (slug, payload) => client.post(`/admin/branches/${slug}`, payload).then((r) => r.data.data),
    deleteBranch: (slug) => client.delete(`/admin/branches/${slug}`).then((r) => r.data),

    categories: (params = {}) => client.get('/admin/categories', { params }).then((r) => r.data.data),
    createCategory: (payload) => client.post('/admin/categories', payload).then((r) => r.data.data),
    updateCategory: (slug, payload) => client.post(`/admin/categories/${slug}`, payload).then((r) => r.data.data),
    deleteCategory: (slug) => client.delete(`/admin/categories/${slug}`).then((r) => r.data),

    brands: () => client.get('/admin/brands').then((r) => r.data.data),
    createBrand: (payload) => client.post('/admin/brands', payload).then((r) => r.data.data),
    updateBrand: (slug, payload) => client.post(`/admin/brands/${slug}`, payload).then((r) => r.data.data),
    deleteBrand: (slug) => client.delete(`/admin/brands/${slug}`).then((r) => r.data),

    posts: (params = {}) => client.get('/admin/posts', { params }).then((r) => r.data),
    post: (slug) => client.get(`/admin/posts/${slug}`).then((r) => r.data.data),
    createPost: (payload) => client.post('/admin/posts', payload).then((r) => r.data.data),
    updatePost: (slug, payload) => client.post(`/admin/posts/${slug}`, payload).then((r) => r.data.data),
    deletePost: (slug) => client.delete(`/admin/posts/${slug}`).then((r) => r.data),

    careers: (params = {}) => client.get('/admin/careers', { params }).then((r) => r.data),
    createCareer: (payload) => client.post('/admin/careers', payload).then((r) => r.data.data),
    updateCareer: (slug, payload) => client.post(`/admin/careers/${slug}`, payload).then((r) => r.data.data),
    deleteCareer: (slug) => client.delete(`/admin/careers/${slug}`).then((r) => r.data),

    banners: () => client.get('/admin/banners').then((r) => r.data.data),
    // Banners always carry an image, so these two are multipart by definition.
    createBanner: (payload) => client.post('/admin/banners', toFormData(payload)).then((r) => r.data.data),
    updateBanner: (id, payload) => client.post(`/admin/banners/${id}`, toFormData(payload)).then((r) => r.data.data),
    deleteBanner: (id) => client.delete(`/admin/banners/${id}`).then((r) => r.data),

    gallery: () => client.get('/admin/gallery').then((r) => r.data.data),
    uploadGallery: (payload) => client.post('/admin/gallery', toFormData(payload)).then((r) => r.data.data),
    deleteGalleryImage: (id) => client.delete(`/admin/gallery/${id}`).then((r) => r.data),

    inquiries: (params = {}) => client.get('/admin/inquiries', { params }).then((r) => r.data),
    toggleInquiryHandled: (id) => client.patch(`/admin/inquiries/${id}/handled`).then((r) => r.data.data),

    vouchers: (params = {}) => client.get('/admin/vouchers', { params }).then((r) => r.data),
    createVoucher: (payload) => client.post('/admin/vouchers', payload).then((r) => r.data.data),
    updateVoucher: (code, payload) => client.patch(`/admin/vouchers/${code}`, payload).then((r) => r.data.data),
    deleteVoucher: (code) => client.delete(`/admin/vouchers/${code}`).then((r) => r.data),

    users: (params = {}) => client.get('/admin/users', { params }).then((r) => r.data),
    roles: () => client.get('/admin/roles').then((r) => r.data.data),
    createUser: (payload) => client.post('/admin/users', payload).then((r) => r.data.data),
    updateUser: (id, payload) => client.patch(`/admin/users/${id}`, payload).then((r) => r.data.data),
    deleteUser: (id) => client.delete(`/admin/users/${id}`).then((r) => r.data),
};
