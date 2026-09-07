import { createApp } from 'vue';
import { createPinia } from 'pinia';

import router from '@/router';
import App from '@/App.vue';
import { formatMoney, formatDate, formatDateTime } from '@/support/format';
import { reveal } from '@/support/reveal';

const app = createApp(App);

app.use(createPinia());
app.use(router);

// Scroll reveal, used by the storefront's editorial sections.
app.directive('reveal', reveal);

app.config.globalProperties.$money = formatMoney;
app.config.globalProperties.$date = formatDate;
app.config.globalProperties.$datetime = formatDateTime;

app.mount('#app');
