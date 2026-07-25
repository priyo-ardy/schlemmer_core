import '../css/app.css';
import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Global Helper untuk cek permission di semua file Vue / Sidebar
        app.config.globalProperties.$can = function (permissionName) {
            const permissions = this.$page.props.auth?.permissions || [];
            return permissions.includes(permissionName);
        };

        app.use(plugin)
            .use(Vue3Toastify, {
                position: 'top-center',
                autoClose: 3000,
                theme: 'colored',
            })
            .mount(el);
    },
});
