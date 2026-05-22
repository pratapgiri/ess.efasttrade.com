import { Car, Plane, Receipt } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/** Internal claim type keys (stable for backend mapping). */
export type ClaimTypeKey = 'expense' | 'local_conveyance' | 'intercity_travel';

export type ClaimVisibilityConfigKey =
    | 'EXPENSES_AVAILABLE'
    | 'LOCAL_CONVEYANCE_AVAILABLE'
    | 'INTERCITY_CONVEYANCE_AVAILABLE'
    | 'ALLOW_EXPENSES_CURRENT_MONTH_ONLY';

export type ClaimVisibilityConfig = Record<ClaimVisibilityConfigKey, boolean>;

export interface ClaimTypeDefinition {
    key: ClaimTypeKey;
    label: string;
    icon: LucideIcon;
    enabledConfig: ClaimVisibilityConfigKey;
    routeName?: string;
}

export const CLAIM_TYPES: ClaimTypeDefinition[] = [
    {
        key: 'expense',
        label: 'Expenses',
        icon: Receipt,
        enabledConfig: 'EXPENSES_AVAILABLE',
        routeName: 'EXPENSE_ROUTE',
    },
    {
        key: 'local_conveyance',
        label: 'Local Conveyance',
        icon: Car,
        enabledConfig: 'LOCAL_CONVEYANCE_AVAILABLE',
        routeName: 'CONVEYANCE_ROUTE',
    },
    {
        key: 'intercity_travel',
        label: 'Inter City Travel',
        icon: Plane,
        enabledConfig: 'INTERCITY_CONVEYANCE_AVAILABLE',
        routeName: 'TRAVEL_ROUTE',
    },
];

export const DEFAULT_VISIBILITY: ClaimVisibilityConfig = {
    EXPENSES_AVAILABLE: true,
    LOCAL_CONVEYANCE_AVAILABLE: true,
    INTERCITY_CONVEYANCE_AVAILABLE: true,
    ALLOW_EXPENSES_CURRENT_MONTH_ONLY: true,
};

export function getVisibleClaimTypes(visibility: ClaimVisibilityConfig = DEFAULT_VISIBILITY): ClaimTypeDefinition[] {
    return CLAIM_TYPES.filter((type) => visibility[type.enabledConfig] !== false);
}

export function getDefaultClaimType(visibility: ClaimVisibilityConfig = DEFAULT_VISIBILITY): ClaimTypeKey {
    const visible = getVisibleClaimTypes(visibility);
    return visible[0]?.key ?? 'expense';
}

export function getClaimTypeDefinition(key: ClaimTypeKey): ClaimTypeDefinition | undefined {
    return CLAIM_TYPES.find((t) => t.key === key);
}

export function getClaimTypeLabel(key: ClaimTypeKey): string {
    return getClaimTypeDefinition(key)?.label ?? key;
}
