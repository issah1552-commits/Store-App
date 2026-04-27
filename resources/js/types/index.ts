import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | string | null;
    isActive?: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

export interface SharedData {
    name: string;
    app: {
        currency: string;
        timezone: string;
    };
    auth: Auth;
    navigation: NavGroup[];
    [key: string]: unknown;
}

export interface RoleSummary {
    name: string;
    display_name: string;
}

export interface LocationSummary {
    id: number;
    name: string;
    code: string;
    type: string;
    region_name?: string | null;
}

export interface User {
    id: number;
    name: string;
    username?: string;
    email: string;
    phone?: string | null;
    avatar?: string;
    is_active?: boolean;
    role?: RoleSummary | null;
    default_location?: LocationSummary | null;
    assigned_locations?: LocationSummary[];
    permissions?: string[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
