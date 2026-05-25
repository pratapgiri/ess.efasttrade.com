// pages/hr/claims/index.tsx — My Claims (create + view only)
import { useCallback, useMemo, useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import {
    ClaimTabs,
    ClaimFilters,
    ClaimTable,
    ClaimFormModal,
    ClaimViewModal,
    getMyClaimsColumns,
    getMyClaimsActions,
} from '@/components/claims';
import { DEFAULT_VISIBILITY, getDefaultClaimType, getVisibleClaimTypes } from '@/config/claims';
import type { ClaimTypeKey, ClaimVisibilityConfig } from '@/config/claims';
import { toast } from '@/components/custom-toast';
import { fetchClaimDetail, submitNewClaim, submitClaimUpdate } from '@/utils/claim-api';
import { assertExpenseClaimDateAllowed } from '@/utils/claim-validation';
import type { ClaimSubmitCallbacks } from '@/utils/claim-api';
import type { ClaimDetail, ClaimFormAction, ClaimFormValues, ClaimListItem } from '@/types/claims';

type PaginatedClaims = {
    data: ClaimListItem[];
    current_page: number;
    last_page: number;
    from: number;
};

export default function MyClaims() {
    const { t } = useTranslation();
    const { auth, claims, claimConfig, filters: pageFilters = {} } = usePage().props as {
        auth?: { permissions?: string[] };
        claims?: PaginatedClaims;
        claimConfig?: ClaimVisibilityConfig;
        filters?: Record<string, string>;
    };
    const permissions = auth?.permissions ?? [];

    const visibility = claimConfig ?? DEFAULT_VISIBILITY;
    const resolveClaimType = (preferred?: string): ClaimTypeKey => {
        const visible = getVisibleClaimTypes(visibility);
        const candidate = (preferred as ClaimTypeKey) || getDefaultClaimType(visibility);
        return visible.some((tab) => tab.key === candidate) ? candidate : getDefaultClaimType(visibility);
    };
    const [claimType, setClaimType] = useState<ClaimTypeKey>(() => resolveClaimType(pageFilters.claim_type));
    const [monthYear, setMonthYear] = useState(pageFilters.month_year || new Date().toISOString().slice(0, 7));
    const [status, setStatus] = useState(pageFilters.status || 'all');
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');

    const [createOpen, setCreateOpen] = useState(false);
    const [viewOpen, setViewOpen] = useState(false);
    const [modalMode, setModalMode] = useState<'view' | 'edit'>('view');
    const [selectedClaim, setSelectedClaim] = useState<ClaimDetail | null>(null);
    const [loadingDetail, setLoadingDetail] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const canCreate =
        permissions.includes('create-claims') ||
        permissions.includes('manage-own-claims');

    const rows = claims?.data ?? [];
    const columns = useMemo(() => getMyClaimsColumns(t), [t]);
    const tableActions = useMemo(() => getMyClaimsActions(t), [t]);

    const claimsVisitOptions = {
        preserveState: true,
        preserveScroll: true,
        only: ['claims', 'filters'],
    };

    const applyFilters = useCallback(
        (overrides: Record<string, string | number | undefined> = {}) => {
            router.get(
                route('hr.claims.index'),
                {
                    claim_type: claimType,
                    month_year: monthYear,
                    status: status !== 'all' ? status : undefined,
                    search: searchTerm || undefined,
                    per_page: pageFilters.per_page ?? 10,
                    ...overrides,
                },
                claimsVisitOptions
            );
        },
        [claimType, monthYear, status, searchTerm, pageFilters.per_page]
    );

    const handleTabChange = (type: ClaimTypeKey) => {
        setClaimType(type);
        router.get(
            route('hr.claims.index'),
            {
                claim_type: type,
                month_year: monthYear,
                status: status !== 'all' ? status : undefined,
                search: searchTerm || undefined,
            },
            claimsVisitOptions
        );
    };

    const loadClaimDetail = async (row: ClaimListItem): Promise<ClaimDetail | null> => {
        setSelectedClaim(null);
        setViewOpen(true);
        setLoadingDetail(true);
        try {
            const { claim } = await fetchClaimDetail(row.id);
            setSelectedClaim(claim);
            return claim;
        } catch (e) {
            setViewOpen(false);
            const msg = e instanceof Error ? e.message : t('Failed to load claim');
            toast.error(msg);
            return null;
        } finally {
            setLoadingDetail(false);
        }
    };

    const claimSubmitCallbacks = (loadingMessage: string, onSuccess?: () => void): ClaimSubmitCallbacks => ({
        onStart: () => {
            setSubmitting(true);
            toast.loading(loadingMessage);
        },
        onFinish: () => {
            setSubmitting(false);
            toast.dismiss();
        },
        onSuccess: (page: { props: { flash?: { success?: string; error?: string } } }) => {
            onSuccess?.();
            const flash = page.props.flash as { success?: string; error?: string } | undefined;
            if (flash?.success) toast.success(t(flash.success));
            else if (flash?.error) toast.error(t(flash.error));
        },
        onError: (errors: Record<string, string> | string) => {
            const msg =
                typeof errors === 'string' ? errors : Object.values(errors as Record<string, string>).join(', ');
            toast.error(msg ? t('Failed to save claim: {{errors}}', { errors: msg }) : t('Failed to save claim'));
        },
    });

    const handleAction = async (action: string, row: ClaimListItem) => {
        if (action === 'view' || action === 'edit') {
            setModalMode(action === 'edit' ? 'edit' : 'view');
            await loadClaimDetail(row);
        }
    };

    const handleFormSubmit = (action: ClaimFormAction, values: ClaimFormValues, file?: File | null) => {
        const dateError = assertExpenseClaimDateAllowed(values, claimType, visibility);
        if (dateError) {
            toast.error(t(dateError));
            return;
        }

        const loadingMessage =
            action === 'forward' ? t('Submitting claim...') : t('Saving claim...');

        const callbacks = claimSubmitCallbacks(loadingMessage, () => setCreateOpen(false));

        submitNewClaim({ ...values, claim_type: claimType }, action, file, callbacks);
    };

    const handleEditSubmit = (action: ClaimFormAction, values: ClaimFormValues, file?: File | null) => {
        if (!selectedClaim) return;

        if (action !== 'cancel') {
            const dateError = assertExpenseClaimDateAllowed(values, selectedClaim.claim_type, visibility);
            if (dateError) {
                toast.error(t(dateError));
                return;
            }
        }

        const loadingMessage =
            action === 'forward' ? t('Submitting claim...') :
            action === 'cancel'  ? t('Cancelling claim...') :
                                   t('Saving claim...');

        const callbacks = claimSubmitCallbacks(loadingMessage, () => setViewOpen(false));

        submitClaimUpdate(selectedClaim.id, values, action, file ?? null, {}, callbacks);
    };

    return (
        <PageTemplate
            title={t('My Claims')}
            description={t('Submit and track your expense, conveyance, and travel claims')}
            url="/hr/claims"
            actions={
                canCreate
                    ? [
                          {
                              label: t('Add Claim'),
                              icon: <Plus className="mr-2 h-4 w-4" />,
                              variant: 'default' as const,
                              onClick: () => setCreateOpen(true),
                          },
                      ]
                    : []
            }
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('HR Management') },
                { title: t('My Claims') },
            ]}
        >
            <div className="space-y-4">
                <ClaimTabs value={claimType} onChange={handleTabChange} visibility={visibility} />

                <ClaimFilters
                    monthYear={monthYear}
                    onMonthYearChange={(v) => {
                        setMonthYear(v);
                        applyFilters({ month_year: v, page: 1 });
                    }}
                    status={status}
                    onStatusChange={(v) => {
                        setStatus(v);
                        applyFilters({ ...(v !== 'all' ? { status: v } : {}), page: 1 });
                    }}
                    showStatusFilter
                    searchTerm={searchTerm}
                    onSearchChange={setSearchTerm}
                    onSearch={() => applyFilters({ page: 1 })}
                />

                <ClaimTable
                    columns={columns}
                    actions={tableActions}
                    rows={rows}
                    from={claims?.from ?? 1}
                    permissions={permissions}
                    onAction={(action, row) => handleAction(action, row as ClaimListItem)}
                    emptyTitle={t('No claims for this period')}
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

            <ClaimFormModal
                open={createOpen}
                onOpenChange={setCreateOpen}
                claimType={claimType}
                onSubmit={handleFormSubmit}
                submitting={submitting}
            />

            <ClaimViewModal
                open={viewOpen}
                onOpenChange={setViewOpen}
                detail={selectedClaim}
                loading={loadingDetail}
                initialMode={modalMode}
                onFormAction={handleEditSubmit}
                submitting={submitting}
            />
        </PageTemplate>
    );
}
