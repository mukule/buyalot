import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from '../api/client';
import Login from '../views/Login.vue';
import RegisterSelect from '../views/RegisterSelect.vue';
import Terminal from '../views/Terminal.vue';

const router = createRouter({
    history: createWebHistory('/'),
    routes: [
        { path: '/', redirect: '/login' },
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/registers', name: 'registers', component: RegisterSelect, meta: { auth: true } },
        { path: '/terminal', name: 'terminal', component: Terminal, meta: { auth: true } },
    ],
});

router.beforeEach((to, _from, next) => {
    const token = getToken();
    if (to.meta.auth && !token) {
        next({ name: 'login' });
    } else if (to.meta.guest && token) {
        next({ name: 'registers' });
    } else {
        next();
    }
});

export default router;
