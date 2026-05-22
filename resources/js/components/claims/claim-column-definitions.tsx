import type { TFunction } from 'i18next';
import type { TableAction, TableColumn } from '@/types/crud';
import { ClaimStatusBadge } from './claim-status-badge';
import { getClaimTypeLabel } from '@/config/claims';
import type { ClaimListItem } from '@/types/claims';
import { Paperclip } from 'lucide-react';

export function getMyClaimsColumns(t: TFunction): TableColumn[] {
    return [
        { key: 'claim_no', label: t('Claim No'), sortable: true },
        { key: 'date', label: t('Date'), sortable: true },
        {
            key: 'claim_type',
            label: t('Claim Type'),
            render: (_: unknown, row: ClaimListItem) => t(getClaimTypeLabel(row.claim_type)),
        },
        {
            key: 'amount',
            label: t('Amount'),
            sortable: true,
            render: (v: number) => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }),
        },
        {
            key: 'status',
            label: t('Status'),
            render: (v: string) => <ClaimStatusBadge status={v} />,
        },
        {
            key: 'pending_at',
            label: t('Pending At'),
            render: (v: string) => <span className="capitalize">{v}</span>,
        },
        {
            key: 'narration',
            label: t('Narration'),
            render: (v: string) => (
                <span className="line-clamp-1 max-w-[200px]" title={v}>
                    {v}
                </span>
            ),
        },
        {
            key: 'has_attachment',
            label: t('Attachment'),
            render: (_: unknown, row: ClaimListItem) =>
                row.has_attachment ? (
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground" title={row.attachment_name}>
                        <Paperclip className="h-3.5 w-3.5" />
                        {row.attachment_name ? (
                            <span className="max-w-[100px] truncate">{row.attachment_name}</span>
                        ) : (
                            t('Yes')
                        )}
                    </span>
                ) : (
                    <span className="text-xs text-muted-foreground">—</span>
                ),
        },
    ];
}

export function getMyClaimsActions(t: TFunction): TableAction[] {
    return [
        {
            label: t('View'),
            icon: 'Eye',
            action: 'view',
            className: 'text-blue-500',
        },
        {
            label: t('Edit'),
            icon: 'Pencil',
            action: 'edit',
            className: 'text-amber-500',
            condition: (row: ClaimListItem) =>
                row.status === 'draft' || row.status === 'pending',
        },
    ];
}

export function getApprovalClaimsColumns(t: TFunction): TableColumn[] {
    return getMyClaimsColumns(t);
}

export function getApprovalClaimsActions(t: TFunction): TableAction[] {
    return getMyClaimsActions(t);
}
