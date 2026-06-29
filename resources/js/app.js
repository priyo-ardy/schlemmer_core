import '../css/app.css';
import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        // const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        // return pages[`./Pages/${name}.vue`];
        return pages[`./Pages/${name}.vue`]();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Vue3Toastify, {
                position: 'top-center',
                autoClose: 3000,
                theme: 'colored',
            })
            .mount(el);
    },
});