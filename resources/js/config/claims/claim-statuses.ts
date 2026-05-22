import type { ClaimStatus } from '@/types/claims';

export type ClaimStatusTone = 'default' | 'secondary' | 'destructive' | 'outline';

export interface ClaimStatusDefinition {
    key: ClaimStatus;
    label: string;
    badgeVariant: ClaimStatusTone;
    timelineState: 'completed' | 'current' | 'pending' | 'rejected';
    className?: string;
}

export const CLAIM_STATUSES: Record<ClaimStatus, ClaimStatusDefinition> = {
    draft: {
        key: 'draft',
        label: 'Draft',
        badgeVariant: 'secondary',
        timelineState: 'pending',
        className: 'bg-muted text-muted-foreground',
    },
    pending: {
        key: 'pending',
        label: 'Pending',
        badgeVariant: 'default',
        timelineState: 'current',
        className: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    },
    approved: {
        key: 'approved',
        label: 'Approved',
        badgeVariant: 'default',
        timelineState: 'completed',
        className: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    },
    rejected: {
        key: 'rejected',
        label: 'Rejected',
        badgeVariant: 'destructive',
        timelineState: 'rejected',
    },
    cancelled: {
        key: 'cancelled',
        label: 'Cancelled',
        badgeVariant: 'outline',
        timelineState: 'rejected',
    },
};

export const CLAIM_STATUS_FILTER_OPTIONS: { value: string; label: string }[] = [
    { value: 'all', label: 'All Statuses' },
    ...Object.values(CLAIM_STATUSES).map((s) => ({ value: s.key, label: s.label })),
];

export const WORKFLOW_ROLE_TYPES = ['Employee', 'Manager', 'HR', 'Accounts'] as const;

export type WorkflowRoleType = (typeof WORKFLOW_ROLE_TYPES)[number];

export type WorkflowStepState = 'completed' | 'current' | 'pending' | 'rejected';

export interface WorkflowStepDisplay {
    serial_number: number;
    emp_type: string;
    state: WorkflowStepState;
}
