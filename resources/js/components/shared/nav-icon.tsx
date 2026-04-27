import {
    ArrowLeftRight,
    ChartColumn,
    LayoutGrid,
    MapPinned,
    PackageSearch,
    ReceiptText,
    ShoppingCart,
    Users,
    type LucideIcon,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    'arrow-left-right': ArrowLeftRight,
    'chart-column': ChartColumn,
    'layout-grid': LayoutGrid,
    'map-pinned': MapPinned,
    'package-search': PackageSearch,
    'receipt-text': ReceiptText,
    'shopping-cart': ShoppingCart,
    users: Users,
};

export function resolveNavIcon(icon?: string | null): LucideIcon | null {
    if (!icon) {
        return null;
    }

    return iconMap[icon] ?? null;
}
