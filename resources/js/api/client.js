import axios from 'axios';

/**
 * The single HTTP client for the whole SPA.
 *
 * Sanctum stateful mode: the session travels in a cookie that JavaScript cannot
 * read, so there is no token in localStorage for an XSS to steal.
 */
const client = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

let csrfReady = null;

/**
 * Fetch the CSRF cookie once, before the first state-changing request.
 */
export function ensureCsrfCookie() {
    csrfReady ??= axios.get('/sanctum/csrf-cookie', { withCredentials: true });

    return csrfReady;
}

client.interceptors.request.use(async (config) => {
    if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
        await ensureCsrfCookie();
    }

    return config;
});

/**
 * Normalise errors so callers deal with one shape.
 */
client.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        // A stale CSRF token means the session expired; refetch it next time.
        if (status === 419) {
            csrfReady = null;
        }

        return Promise.reject({
            status,
            message: error.response?.data?.message ?? 'Something went wrong. Please try again.',
            errors: error.response?.data?.errors ?? {},
            original: error,
        });
    },
);

export default client;
