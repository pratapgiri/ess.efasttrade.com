import type { ClaimTypeKey, ClaimVisibilityConfig } from '@/config/claims';
import type { ClaimFormValues } from '@/types/claims';

/**
 * Client-side guard for expense date month rule (server enforces the same rule).
 */
export function assertExpenseClaimDateAllowed(
    values: ClaimFormValues,
    claimType: ClaimTypeKey,
    visibility: ClaimVisibilityConfig
): string | null {
    if (claimType !== 'expense' || visibility.ALLOW_EXPENSES_CURRENT_MONTH_ONLY === false) {
        return null;
    }

    const claimMonth = values.claim_date?.slice(0, 7);
    const currentMonth = new Date().toISOString().slice(0, 7);

    if (!claimMonth || claimMonth !== currentMonth) {
        return 'Expense not allowed for selected month';
    }

    return null;
}
