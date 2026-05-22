import type { ClaimVisibilityConfig, ClaimVisibilityConfigKey } from './claim-types';

export interface CompanyConfigToggleDefinition {
    key: ClaimVisibilityConfigKey;
    label: string;
    description: string;
}

export const COMPANY_CONFIG_TOGGLES: CompanyConfigToggleDefinition[] = [
    {
        key: 'EXPENSES_AVAILABLE',
        label: 'Expense Claims Available',
        description: 'Employee can raise request for expenses',
    },
    {
        key: 'LOCAL_CONVEYANCE_AVAILABLE',
        label: 'Local Conveyance Available',
        description: 'Employee can raise request for Local Conveyance',
    },
    {
        key: 'INTERCITY_CONVEYANCE_AVAILABLE',
        label: 'Intercity Travel Available',
        description: 'Employee can raise request for Inter city Travel expenses',
    },
];

export function mapInertiaConfigToVisibility(config?: {
    local_conveyance_available?: boolean;
    intercity_conveyance_available?: boolean;
    expenses_available?: boolean;
}): ClaimVisibilityConfig {
    if (!config) {
        return {
            EXPENSES_AVAILABLE: true,
            LOCAL_CONVEYANCE_AVAILABLE: true,
            INTERCITY_CONVEYANCE_AVAILABLE: true,
            ALLOW_EXPENSES_CURRENT_MONTH_ONLY: true,
        };
    }
    return {
        EXPENSES_AVAILABLE: !!config.expenses_available,
        LOCAL_CONVEYANCE_AVAILABLE: !!config.local_conveyance_available,
        INTERCITY_CONVEYANCE_AVAILABLE: !!config.intercity_conveyance_available,
        ALLOW_EXPENSES_CURRENT_MONTH_ONLY: true,
    };
}
