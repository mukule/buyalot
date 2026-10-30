import type { SimplifiedProduct } from '@/types';

/**
 * Map an item from FrontendProductService::normalizeVariant to the shape ProductCard expects.
 */
export function toSimplifiedProduct(p: any): SimplifiedProduct {
    return {
        id: p.id,
        hashid: p.variant_hashid,
        product_slug: p.product_slug,
        name: p.name,
        brand: p.brand ?? null,
        image: p.primary_image_url || '/fallback-image.png',
        marked_price: p.marked_price ?? 0,
        final_price: p.final_price ?? 0,
        discount_percent: p.discount_percent ?? 0,
        has_discount: p.has_discount ?? false,
        deal_percent: p.deal_percent ?? null,
        rating: 3,
    };
}

export interface CampaignSummary {
    id?: number;
    title: string;
    slug: string;
    type: 'daily_deal' | 'offer' | 'seasonal';
    type_label: string;
    subtitle: string | null;
    description: string | null;
    banner_url: string | null;
    theme_color: string | null;
    discount_percent?: number | null;
    starts_at?: string | null;
    ends_at: string | null;
    products?: any[];
    products_count?: number;
}

export const campaignHref = (c: Pick<CampaignSummary, 'slug'>) => `/deals/${encodeURIComponent(c.slug)}`;
