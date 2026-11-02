import { reactive, computed } from 'vue';
import api from './api';

const getCachedUser = () => {
    try {
        return JSON.parse(localStorage.getItem('sisqa_user') || 'null');
    } catch {
        return null;
    }
};

const state = reactive({
    token: localStorage.getItem('sisqa_token') || null,
    user: getCachedUser(),
    loading: false,
});

export const useAuth = () => {
    const getActiveUser = () => {
        if (state.user) return state.user;
        const cached = getCachedUser();
        if (cached) state.user = cached;
        return cached;
    };

    const isAuthenticated = computed(() => !!state.token);
    const isAdmin = computed(() => getActiveUser()?.role === 'admin');
    const currentUser = computed(() => getActiveUser());

    const hasMenuAccess = (menuPath) => {
        const u = getActiveUser();
        if (!u) return false;
        if (u.role === 'admin') return true;
        if (menuPath === '/profile') return true;
        const menus = u.permissions?.menus;
        const hasConfiguredMenu = Array.isArray(menus) && menus.includes(menuPath);
        const hasMasterMenu = Array.isArray(u.permissions?.master_menus)
            && u.permissions.master_menus.some((menu) => menu.url_or_route === menuPath);
        return hasConfiguredMenu || hasMasterMenu;
    };

    const getFirstAllowedPath = (customUser = null) => {
        const u = customUser || getActiveUser();
        if (!u) return '/login';
        if (u.role === 'admin') return '/';
        const menus = u.permissions?.menus;
        const availableMenus = [
            '/',
            '/schedules',
            '/test-cases',
            '/bugs',
            '/retests',
            '/flows',
            '/guides',
            '/reports',
            '/reports/wa',
            '/reports/weekly',
            '/activity-logs',
        ];
        return Array.isArray(menus)
            ? availableMenus.find((menuPath) => menus.includes(menuPath)) || '/profile'
            : '/profile';
    };

    const hasFeatureAccess = (featureKey) => {
        const u = getActiveUser();
        if (!u) return false;
        if (u.role === 'admin') return true;
        const features = u.permissions?.features;
        return Array.isArray(features) && features.includes(featureKey);
    };

    const login = async (loginInput, password) => {
        state.loading = true;
        try {
            const response = await api.post('/login', {
                login: loginInput,
                password,
            });

            state.token = response.data.token;
            state.user = response.data.user;

            localStorage.setItem('sisqa_token', state.token);
            localStorage.setItem('sisqa_user', JSON.stringify(state.user));

            return response.data;
        } finally {
            state.loading = false;
        }
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch (e) {
            console.error('Logout error:', e);
        } finally {
            state.token = null;
            state.user = null;
            localStorage.removeItem('sisqa_token');
            localStorage.removeItem('sisqa_user');
            window.location.href = '/login';
        }
    };

    const checkAuth = async () => {
        const activeToken = state.token || localStorage.getItem('sisqa_token');
        if (!activeToken) return null;
        try {
            const response = await api.get('/user');
            if (response.data?.user) {
                state.user = response.data.user;
                localStorage.setItem('sisqa_user', JSON.stringify(state.user));
            }
            return state.user;
        } catch (e) {
            if (e.response?.status === 401) {
                state.token = null;
                state.user = null;
                localStorage.removeItem('sisqa_token');
                localStorage.removeItem('sisqa_user');
                return null;
            }
            console.error('Authentication verification failed:', e);
            state.user = null;
            localStorage.removeItem('sisqa_user');
            return null;
        }
    };

    const setUser = (user) => {
        state.user = user;
        localStorage.setItem('sisqa_user', JSON.stringify(user));
    };

    return {
        state,
        isAuthenticated,
        isAdmin,
        currentUser,
        hasMenuAccess,
        hasFeatureAccess,
        getFirstAllowedPath,
        login,
        logout,
        checkAuth,
        setUser,
    };
};
