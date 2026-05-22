import { CrudTable } from '@/components/CrudTable';
import type { TableAction, TableColumn } from '@/types/crud';
import { ClaimEmptyState } from './claim-empty-state';
import { ClaimTableSkeleton } from './claim-table-skeleton';

interface ClaimTableProps {
    columns: TableColumn[];
    actions: TableAction[];
    rows: Record<string, unknown>[];
    loading?: boolean;
    from?: number;
    onAction: (action: string, row: Record<string, unknown>) => void;
    emptyTitle?: string;
    emptyDescription?: string;
    permissions?: string[];
}

export function ClaimTable({
    columns,
    actions,
    rows,
    loading = false,
    from = 1,
    onAction,
    emptyTitle,
    emptyDescription,
    permissions = [],
}: ClaimTableProps) {
    if (loading) {
        return <ClaimTableSkeleton columns={columns.length + 1} />;
    }

    if (rows.length === 0) {
        return <ClaimEmptyState title={emptyTitle} description={emptyDescription} />;
    }

    return (
        <div className="overflow-x-auto rounded-lg border bg-card">
            <CrudTable
                columns={columns}
                actions={actions}
                data={rows}
                from={from}
                onAction={onAction}
                permissions={permissions}
                showActions
            />
        </div>
    );
}
