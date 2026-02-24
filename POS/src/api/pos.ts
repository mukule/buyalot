import { api, clearToken, setToken } from './client';

const BASE = '/api/pos';

export interface LoginPayload {
    username: string;
    pin: string;
    terminal_id?: number;
    opening_balance?: number;
}

export interface LoginResponse {
    message: string;
    user: { id: number; name: string; email: string };
    token: string;
    token_type: string;
    terminal?: { session_id: number; register_id: number; register_name: string };
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
    const { data } = await api.post<LoginResponse>(`${BASE}/login`, payload);
    setToken(data.token);
    return data;
}

export async function logout(): Promise<void> {
    try {
        await api.post(`${BASE}/logout`);
    } finally {
        clearToken();
    }
}

export async function getSession(): Promise<{ session: any; settings: any; settings_version: number }> {
    const { data } = await api.get(`${BASE}/session/current`);
    return data;
}

export async function getSettingsVersion(): Promise<{ settings_version: number; seller_id: number | null }> {
    const { data } = await api.get(`${BASE}/settings-version`);
    return data;
}

export async function getFullSettings(): Promise<{ settings: any; settings_version: number; seller_id: number | null }> {
    const { data } = await api.get(`${BASE}/settings`);
    return data;
}

export async function openSession(pos_register_id: number, opening_balance: number) {
    const { data } = await api.post(`${BASE}/sessions/open`, { pos_register_id, opening_balance });
    return data;
}

export async function closeSession(sessionId: number, closing_balance: number, notes?: string) {
    const { data } = await api.post(`${BASE}/sessions/${sessionId}/close`, {
        closing_balance,
        notes: notes || '',
    });
    return data;
}

export async function getRegisters(): Promise<{ registers: any[] }> {
    const { data } = await api.get(`${BASE}/registers`);
    return data;
}

export async function getProducts(params?: { search?: string; category_id?: number; brand_id?: number; page?: number }) {
    const { data } = await api.get(`${BASE}/products`, { params });
    return data;
}

export function getPaymentsInitiateUrl(): string {
    return '/payments/initiate';
}

export function getPaymentStatusUrl(reference: string): string {
    return `/payments/requests/${reference}/status`;
}

export async function getCategories() {
    const { data } = await api.get(`${BASE}/categories`);
    return data;
}

export async function getCustomers(search?: string) {
    const { data } = await api.get(`${BASE}/customers`, { params: search ? { search } : {} });
    return data;
}

export async function createCustomer(payload: Record<string, unknown>) {
    const { data } = await api.post(`${BASE}/customers`, payload);
    return data;
}

export async function createSale(payload: {
    pos_session_id: number;
    customer_id: number;
    items: Array<{ product_variant_id: number; quantity: number }>;
    payment_method: string;
    amount_paid: number;
    allocated_payment_ids?: number[];
}) {
    const { data } = await api.post(`${BASE}/sales`, payload);
    return data;
}

export async function getUnallocatedPayments(customer_id: number) {
    const { data } = await api.get(`${BASE}/unallocated-payments`, { params: { customer_id } });
    return data;
}

export async function storeUnallocatedPayment(payload: Record<string, unknown>) {
    const { data } = await api.post(`${BASE}/unallocated-payments`, payload);
    return data;
}

export async function getVoidedSales(pos_session_id: number) {
    const { data } = await api.get(`${BASE}/voided-sales`, { params: { pos_session_id } });
    return data;
}

export async function storeVoidedSale(payload: Record<string, unknown>) {
    const { data } = await api.post(`${BASE}/voided-sales`, payload);
    return data;
}

export async function recallVoidedSale(id: number) {
    const { data } = await api.post(`${BASE}/voided-sales/${id}/recall`);
    return data;
}

export async function verifyAdminPin(pin: string): Promise<{ success: boolean }> {
    const { data } = await api.post(`${BASE}/verify-admin-pin`, { pin });
    return data;
}
