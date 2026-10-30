import { reactive, computed } from 'vue';
import api from './api';

const state = reactive({
    token: localStorage.getItem('sisqa_token') || null,
    user: JSON.parse(localStorage.getItem('sisqa_user') || 'null'),
    loading: false,
});

export const useAuth = () => {
    const getActiveUser = () => {
        if (state.user) return state.user;
        try {
            const cached = JSON.parse(localStorage.getItem('sisqa_user') || 'null');
            if (cached) state.user = cached;
            return cached;
        } catch {
            return null;
        }
    };

    const isAuthenticated = computed(() => !!state.token);
    const isAdmin = computed(() => getActiveUser()?.role === 'admin');
    const currentUser = computed(() => getActiveUser());

    const hasMenuAccess = (menuPath) => {
        const u = getActiveUser();
        if (!u) return true;
        if (u.role === 'admin') return true;
        if (menuPath === '/profile') return true;
        const menus = u.permissions?.menus;
        if (!Array.isArray(menus)) {
            return !menuPath.startsWith('/master') && menuPath !== '/users';
        }
        return menus.includes(menuPath);
    };

    const getFirstAllowedPath = (customUser = null) => {
        const u = customUser || getActiveUser();
        if (!u) return '/login';
        if (u.role === 'admin') return '/';
        const menus = u.permissions?.menus;
        if (Array.isArray(menus)) {
            return menus.length > 0 ? menus[0] : '/profile';
        }
        return '/';
    };

    const hasFeatureAccess = (featureKey) => {
        const u = getActiveUser();
        if (!u) return true;
        if (u.role === 'admin') return true;
        const features = u.permissions?.features;
        if (!Array.isArray(features)) {
            return [
                'execute_test',
                'create_test',
                'import_test_excel',
                'create_bug',
                'edit_bug',
                'import_bug_excel',
                'create_retest',
                'manage_flow',
                'export_report',
            ].includes(featureKey);
        }
        return features.includes(featureKey);
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
            return getActiveUser();
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
