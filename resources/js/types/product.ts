export interface ImageItem {
    id?: string | number;
    url?: string;
    preview: string;
    file: File | null;
    is_primary: boolean;
}

export interface VariantRow {
    id?: number | null;
    sku?: string | null;
    values: Record<string, string>;
    buying_price: number;
    marked_price: number;
    stock: number;
    images: ImageItem[];
}
