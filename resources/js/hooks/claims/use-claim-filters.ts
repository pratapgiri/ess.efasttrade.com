import { useMemo, useState } from 'react';
import type { ClaimListItem, ClaimStatus } from '@/types/claims';
import type { ClaimTypeKey } from '@/config/claims';

export interface ClaimFilterState {
    claimType: ClaimTypeKey;
    monthYear: string;
    status: string;
    searchTerm: string;
    employeeId: string;
}

export function useClaimFilters(initialMonthYear?: string) {
    const [claimType, setClaimType] = useState<ClaimTypeKey>('expense');
    const [monthYear, setMonthYear] = useState(
        initialMonthYear ?? new Date().toISOString().slice(0, 7)
    );
    const [status, setStatus] = useState('all');
    const [searchTerm, setSearchTerm] = useState('');
    const [employeeId, setEmployeeId] = useState('all');

    return {
        claimType,
        setClaimType,
        monthYear,
        setMonthYear,
        status,
        setStatus,
        searchTerm,
        setSearchTerm,
        employeeId,
        setEmployeeId,
    };
}

function matchesMonthYear(dateStr: string, monthYear: string): boolean {
    if (!monthYear) return true;
    return dateStr.startsWith(monthYear);
}

export function filterClaimList(
    rows: ClaimListItem[],
    filters: Partial<ClaimFilterState>
): ClaimListItem[] {
    const { claimType, monthYear, status, searchTerm, employeeId } = filters;

    return rows.filter((row) => {
        if (claimType && row.claim_type !== claimType) return false;
        if (monthYear && !matchesMonthYear(row.date, monthYear)) return false;
        if (status && status !== 'all' && row.status !== (status as ClaimStatus)) return false;
        if (employeeId && employeeId !== 'all' && String(row.employee_id) !== employeeId) return false;
        if (searchTerm) {
            const q = searchTerm.toLowerCase();
            const haystack = [row.claim_no, row.narration, row.employee_name ?? '', row.pending_at]
                .join(' ')
                .toLowerCase();
            if (!haystack.includes(q)) return false;
        }
        return true;
    });
}

export function useFilteredClaims(rows: ClaimListItem[], filters: Partial<ClaimFilterState>) {
    return useMemo(() => filterClaimList(rows, filters), [rows, filters]);
}

export function getDistinctPendingEmployees(rows: ClaimListItem[]) {
    const map = new Map<number, string>();
    rows
        .filter((r) => r.status === 'pending' && r.employee_id != null)
        .forEach((r) => {
            if (r.employee_id != null && r.employee_name) {
                map.set(r.employee_id, r.employee_name);
            }
        });
    return Array.from(map.entries()).map(([id, name]) => ({ id, name }));
}
