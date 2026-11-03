import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import axios from 'axios'
import DialogHost from './Components/DialogHost.vue'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

createInertiaApp({
    title: (title) => {
        if (!title) return 'Teknik Sepeda Motor — SMKS Islam 1 Blitar'
        if (title.includes('TSM') || title.includes('SMKS Islam 1 Blitar') || title.includes('Teknik Sepeda Motor')) {
            return title
        }
        return `${title} — TSM SMKS Islam 1 Blitar`
    },
    resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => [h(App, props), h(DialogHost)] })
            .use(plugin)
            .mount(el)
    },
    progress: {
        color: '#1e3a8a',
    },
})
