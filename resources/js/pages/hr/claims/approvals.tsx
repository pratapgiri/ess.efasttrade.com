// pages/hr/claims/approvals.tsx — Claim Approvals
import { useCallback, useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    ClaimTabs,
    ClaimFilters,
    ClaimTable,
    ClaimApprovalModal,
    getApprovalClaimsColumns,
    getApprovalClaimsActions,
} from '@/components/claims';
import type { ApprovalAction } from '@/components/claims';
import { DEFAULT_VISIBILITY, getDefaultClaimType } from '@/config/claims';
import type { ClaimTypeKey, ClaimVisibilityConfig } from '@/config/claims';
import { toast } from '@/components/custom-toast';
import { fetchClaimDetail, submitClaimUpdate } from '@/utils/claim-api';
import { emptyClaimForm, type ClaimDetail, type ClaimListItem } from '@/types/claims';

type PaginatedClaims = {
    data: ClaimListItem[];
    current_page: number;
    last_page: number;
    from: number;
};

export default function ClaimApprovalsPage() {
    const { t } = useTranslation();
    const { claims, employees = [], claimConfig, filters: pageFilters = {} } = usePage().props as {
        claims?: PaginatedClaims;
        employees?: { id: number; name: string }[];
        claimConfig?: ClaimVisibilityConfig;
        filters?: Record<string, string>;
    };

    const visibility = claimConfig ?? DEFAULT_VISIBILITY;
    const [claimType, setClaimType] = useState<ClaimTypeKey>(
        () => (pageFilters.claim_type as ClaimTypeKey) || getDefaultClaimType(visibility)
    );
    const [monthYear, setMonthYear] = useState(pageFilters.month_year || new Date().toISOString().slice(0, 7));
    const [employeeId, setEmployeeId] = useState(pageFilters.employee_id || 'all');
    const [status, setStatus] = useState(pageFilters.status || 'all');
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');

    const [reviewOpen, setReviewOpen] = useState(false);
    const [reviewClaim, setReviewClaim] = useState<ClaimDetail | null>(null);
    const [previousSteps, setPreviousSteps] = useState<{ serial_number: number; emp_type: string }[]>([]);
    const [loadingDetail, setLoadingDetail] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const rows = claims?.data ?? [];
    const columns = useMemo(() => getApprovalClaimsColumns(t), [t]);
    const tableActions = useMemo(() => getApprovalClaimsActions(t), [t]);

    const applyFilters = useCallback(
        (overrides: Record<string, string | number> = {}) => {
            router.get(
                route('hr.claim-approvals.index'),
                {
                    claim_type: claimType,
                    month_year: monthYear,
                    employee_id: employeeId !== 'all' ? employeeId : undefined,
                    status: status !== 'all' ? status : undefined,
                    search: searchTerm || undefined,
                    ...overrides,
                },
                { preserveState: true, preserveScroll: true }
            );
        },
        [claimType, monthYear, employeeId, status, searchTerm]
    );

    const handleAction = async (_action: string, row: ClaimListItem) => {
        setLoadingDetail(true);
        try {
            const { claim } = await fetchClaimDetail(row.id);
            setReviewClaim(claim);
            setPreviousSteps(claim.previous_steps ?? []);
            setReviewOpen(true);
        } catch {
            toast.error(t('Failed to load claim'));
        } finally {
            setLoadingDetail(false);
        }
    };

    const approvalLoadingMessage = (action: ApprovalAction) => {
        if (action === 'approve') return t('Approving claim...');
        if (action === 'reject') return t('Rejecting claim...');
        return t('Processing claim...');
    };

    const handleApprovalAction = (
        action: ApprovalAction,
        payload: { manager_remark: string; final_remark: string; passed_amount: string; reject_to_step: string }
    ) => {
        if (!reviewClaim) return;
        const form = {
            ...emptyClaimForm(reviewClaim.claim_type),
            claim_date: reviewClaim.claim_date,
            amount: reviewClaim.amount,
            narration: reviewClaim.narration,
            bill_no: reviewClaim.bill_no ?? '',
            bill_date: reviewClaim.bill_date ?? '',
            employee_remark: reviewClaim.employee_remark ?? '',
            manager_remark: payload.manager_remark,
            final_remark: payload.final_remark,
            passed_amount: payload.passed_amount,
            details: reviewClaim.details ?? {},
        };
        const extra: Record<string, string | number> = {};
        if (action === 'reject') {
            extra.reject_to_step = payload.reject_to_step;
        }
        toast.loading(approvalLoadingMessage(action));
        setSubmitting(true);
        submitClaimUpdate(reviewClaim.id, form, action, null, extra, {
            onFinish: () => {
                setSubmitting(false);
                toast.dismiss();
            },
            onSuccess: (page) => {
                setReviewOpen(false);
                const flash = page.props.flash as { success?: string; error?: string } | undefined;
                if (flash?.success) toast.success(t(flash.success));
                else if (flash?.error) toast.error(t(flash.error));
            },
            onError: () => toast.error(t('Failed to update claim')),
        });
    };

    return (
        <PageTemplate
            title={t('Claim Approvals')}
            description={t('Review and approve employee claims awaiting your action')}
            url="/hr/claim-approvals"
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('HR Management') },
                { title: t('Claim Approvals') },
            ]}
        >
            <div className="space-y-4">
                <ClaimTabs
                    value={claimType}
                    onChange={(v) => {
                        setClaimType(v);
                        router.get(route('hr.claim-approvals.index'), { claim_type: v, month_year: monthYear }, { preserveState: true });
                    }}
                    visibility={visibility}
                />

                <ClaimFilters
                    monthYear={monthYear}
                    onMonthYearChange={(v) => {
                        setMonthYear(v);
                        applyFilters({ month_year: v, page: 1 });
                    }}
                    status={status}
                    onStatusChange={(v) => {
                        setStatus(v);
                        applyFilters({ status: v !== 'all' ? v : undefined, page: 1 });
                    }}
                    showStatusFilter
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={() => applyFilters({ page: 1 })}
                    employeeId={employeeId}
                    onEmployeeChange={(v) => {
                        setEmployeeId(v);
                        applyFilters({ employee_id: v !== 'all' ? v : undefined, page: 1 });
                    }}
                    employees={employees}
                    showEmployeeFilter
                />

                <ClaimTable
                    columns={columns}
                    actions={tableActions}
                    rows={rows}
                    loading={loadingDetail}
                    from={claims?.from ?? 1}
                    onAction={(_, row) => handleAction('review', row as ClaimListItem)}
                    emptyTitle={t('No pending approvals')}
                />

                {claims && claims.last_page > 1 && (
                    <div className="flex justify-center gap-2 border-t py-4">
                        <button
                            type="button"
                            className="text-sm text-primary disabled:opacity-50"
                            disabled={claims.current_page <= 1}
                            onClick={() => applyFilters({ page: claims.current_page - 1 })}
                        >
                            {t('Previous')}
                        </button>
                        <span className="text-sm text-muted-foreground">
                            {claims.current_page} / {claims.last_page}
                        </span>
                        <button
                            type="button"
                            className="text-sm text-primary disabled:opacity-50"
                            disabled={claims.current_page >= claims.last_page}
                            onClick={() => applyFilters({ page: claims.current_page + 1 })}
                        >
                            {t('Next')}
                        </button>
                    </div>
                )}
            </div>

            <ClaimApprovalModal
                open={reviewOpen}
                onOpenChange={setReviewOpen}
                claim={reviewClaim}
                previousSteps={previousSteps}
                onAction={handleApprovalAction}
                submitting={submitting}
            />
        </PageTemplate>
    );
}
