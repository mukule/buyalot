import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    roles: $roles;
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    children?: NavItem[];
    permissions?: string[];
    requireAllPermissions?: boolean;
    roles?: string[];
    customCheck?: (auth: Auth) => boolean;
}

export interface NavItemWithPermissions extends NavItem {
    permissions: string[];
    requireAllPermissions?: boolean;
    roles?: string[];
    customCheck?: (auth: Auth) => boolean;
}

export interface PermissionChecker {
    hasPermission: (permission: string) => boolean;
    hasAnyPermission: (permissions: string[]) => boolean;
    hasAllPermissions: (permissions: string[]) => boolean;
    hasRole: (role: string) => boolean;
    hasAnyRole: (roles: string[]) => boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    permissions: Permission[];
}

// types.ts

export interface SellerApplicationImage {
    id: number;
    path: string;
    original_name: string;
}

export interface SellerApplication {
    id: number;
    hashid: string;
    business_type: string;
    agreed_to_privacy: boolean;

    // Contact Info
    first_name: string;
    last_name: string;
    contact_email: string;
    contact_phone: string;

    // Identification
    identification_type: string;
    id_number?: string;
    passport_number?: string;
    drivers_license?: string;

    // Business Info
    business_name?: string;
    primary_product_category?: string;
    description?: string;

    // Owner Info
    owner_first_name?: string;
    owner_last_name?: string;
    owner_email?: string;
    owner_phone?: string;

    // Registration / Tax Info
    vat_registered?: string;
    vat_number?: string;
    company_legal_name?: string;
    ke_business_reg_number?: string;
    non_ke_business_reg_number?: string;
    ke_id_number?: string;
    passport_number_sp?: string;
    country?: string;
    nationality?: string;
    monthly_revenue?: string;

    // Operations
    owns_physical_store?: string;
    retail_store_count?: number;
    is_supplier_to_retailers?: string;
    operates_other_marketplaces?: string;
    marketplace_details?: string;
    supplier_retail_count?: number;
    product_count?: number;
    stock_handling?: string;
    product_website?: string;
    product_origin?: string;
    owned_brands?: string;
    licensed_brands?: string;
    product_branding?: string;
    social_media?: string;
    business_summary?: string;

    // Discovery
    discovery_source?: string;
    referrer_email?: string;
    share_with_distributors?: string;

    // Progress tracking
    is_submitted: boolean;
    status: number;
    status_reason?: string;
    verified?: boolean; // New field for verification status

    // Relations
    images?: SellerApplicationImage[];
}

export interface DocumentType {
    id: number;
    name: string;
    description?: string | null;
    created_at: string;
    updated_at: string;
    hashid?: string;
}

export interface SellerDocument {
    id: number;
    user_id: number;
    document_type_id: number;
    file_path: string;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
}

export interface Warehouse {
    id: number;
    hashid?: string;
    name: string;
    slug: string;
    location?: string | null;
    active: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    hashid: string;
    active: boolean;
}

export interface Subcategory {
    id: number;
    category_id: number;
    name: string;
    slug: string;
    active: boolean;
    hashid: string;
}

export interface Brand {
    id: number;
    name: string;
    slug: string;
    active: boolean;
    logo_url?: string | null;
    hashid?: string;
}

declare global {
    interface Window {
        axios: any;
    }
}

export interface UnitType {
    id: number;
    name: string;
    slug: string;
    active: boolean;
    hashid?: string;
}

export interface Unit {
    id: number;
    name: string;
    symbol: string;
    unit_type_id: number;
    active: boolean;
    hashid?: string;
}

export interface Product {
    id: number;
    hashid: string;
    name: string;
    slug: string;
    description: string | null;
    features: string | null;
    specifications: string | null;
    whats_in_the_box: string | null;

    price: number;
    discount: number | null;
    discounted_price: number; // From accessor
    has_discount: boolean;

    stock: number;
    in_stock: boolean;
    is_out_of_stock: boolean;

    meta_title: string | null;
    meta_keywords: string | null;
    meta_description: string | null;

    status: 0 | 1 | 3; // Match database values
    status_label: 'Pending' | 'Public' | 'Private' | 'Unknown'; // From accessor

    owner_type: string;
    owner_id: number | null;

    brand_id: number | null;
    unit_id: number | null;
    subcategory_id: number | null;

    created_at: string | null;
    updated_at: string | null;

    company_legal_name: string | null;

    // Appended media
    primary_image_url: string | null;
    image_urls: string[];

    // Relationships (optional if not always loaded)
    brand?: Brand;
    subcategory?: Subcategory;
    unit?: Unit;
    variants?: Variant[];
    images: {
        id: number;
        image_path: string;
        is_primary: boolean;
    }[];
}

export interface SimplifiedProduct {
    id: number;
    name: string;
    price: number;
    discount: number;
    image: string;
    onSale?: boolean;
    rating?: number;
}

export interface Brand {
    id: number;
    name: string;
    logo_url?: string;
    subcategory_id?: number;
}

export interface Subcategory {
    id: number;
    name: string;
}

export interface Unit {
    id: number;
    name: string;
    short_code?: string;
}

export interface VariantCategory {
    id: number;
    name: string;
    hashid?: string;
    variants?: Variant[];
}

export interface Variant {
    id: number;
    variant_category_id: number;
    value: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    hashid?: string;
    category?: VariantCategory;
}

export type BreadcrumbItemType = BreadcrumbItem;
