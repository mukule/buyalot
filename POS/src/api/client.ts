import axios from 'axios';

/**
 * The backend API base URL, configured via VITE_API_BASE_URL env variable.
 * In production this points to the main buyalot domain (e.g. https://buyalot.com).
 * In development it defaults to localhost:8000 (the Laravel dev server).
 */
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

export const api = axios.create({
    baseURL: API_BASE_URL,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
});

const TOKEN_KEY = 'pos_token';

export function getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
    localStorage.removeItem(TOKEN_KEY);
}

api.interceptors.request.use((config) => {
    const token = getToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            clearToken();
            window.location.href = '/login';
        }
        return Promise.reject(error);
    },
);
