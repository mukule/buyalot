import { api } from './client';
import { ref, readonly } from 'vue';

const BASE = '/api/pos';
const CACHE_KEY = 'pos_settings';
const VERSION_KEY = 'pos_settings_version';
const POLL_INTERVAL_MS = 30_000;

export interface PosSettings {
    id: number;
    seller_id: number | null;
    business_name: string;
    business_address: string | null;
    business_phone: string | null;
    business_email: string | null;
    currency_symbol: string;
    vat_enabled: boolean;
    vat_percentage: number;
    require_admin_void: boolean;
    show_product_images: boolean;
    max_tabs: number;
    product_display_design: string;
    receipt_header: string | null;
    receipt_footer: string | null;
    invoice_prefix: string;
    receipt_prefix: string;
    payment_methods: Array<{
        id: string;
        name: string;
        enabled: boolean;
        show_qr?: boolean;
        qr_value?: string;
    }>;
    settings_version: number;
    [key: string]: unknown;
}

const _settings = ref<PosSettings | null>(null);
let _pollTimer: ReturnType<typeof setInterval> | null = null;

function readCache(): { settings: PosSettings | null; version: number } {
    try {
        const raw = localStorage.getItem(CACHE_KEY);
        const version = Number(localStorage.getItem(VERSION_KEY) || 0);
        return { settings: raw ? JSON.parse(raw) : null, version };
    } catch {
        return { settings: null, version: 0 };
    }
}

function writeCache(settings: PosSettings, version: number): void {
    localStorage.setItem(CACHE_KEY, JSON.stringify(settings));
    localStorage.setItem(VERSION_KEY, String(version));
    _settings.value = settings;
}

export function clearSettingsCache(): void {
    localStorage.removeItem(CACHE_KEY);
    localStorage.removeItem(VERSION_KEY);
    _settings.value = null;
    stopPolling();
}

/**
 * Load settings — returns from localStorage cache instantly if available,
 * then validates against backend version in the background.
 */
export async function loadSettings(forceRefresh = false): Promise<PosSettings> {
    const cached = readCache();

    if (cached.settings && !forceRefresh) {
        _settings.value = cached.settings;
        checkVersionInBackground();
        return cached.settings;
    }

    return fetchFullSettings();
}

async function fetchFullSettings(): Promise<PosSettings> {
    const { data } = await api.get(`${BASE}/settings`);
    const settings: PosSettings = data.settings;
    const version: number = data.settings_version ?? settings.settings_version ?? 1;
    writeCache(settings, version);
    return settings;
}

async function checkVersionInBackground(): Promise<void> {
    try {
        const { data } = await api.get(`${BASE}/settings-version`);
        const remoteVersion = data.settings_version;
        const localVersion = Number(localStorage.getItem(VERSION_KEY) || 0);

        if (remoteVersion !== localVersion) {
            await fetchFullSettings();
        }
    } catch {
        // Silently fail — we still have cached settings
    }
}

export function startPolling(): void {
    if (_pollTimer) return;
    _pollTimer = setInterval(checkVersionInBackground, POLL_INTERVAL_MS);
}

export function stopPolling(): void {
    if (_pollTimer) {
        clearInterval(_pollTimer);
        _pollTimer = null;
    }
}

export const cachedSettings = readonly(_settings);
