export { ClaimTabs, getDefaultClaimType } from './claim-tabs';
export { ClaimStatusBadge } from './claim-status-badge';
export { ClaimFilters } from './claim-filters';
export { ClaimTable } from './claim-table';
export { ClaimForm } from './claim-form';
export { ClaimFormModal } from './claim-form-modal';
export { ClaimViewModal } from './claim-view-modal';
export { ClaimCreateActions } from './claim-create-actions';
/** Legacy pages only — not used by My Claims index */
export { ClaimApprovalModal } from './claim-approval-modal';
export { ClaimCompanyConfigCard } from './claim-company-config-card';
export { WorkflowLevelCard } from './workflow-level-card';
export { ClaimAttachmentPreview } from './claim-attachment-preview';
export { ClaimAmountCard } from './ClaimAmountCard';
export { ClaimEmptyState } from './claim-empty-state';
export { ClaimTableSkeleton } from './claim-table-skeleton';
export { ClaimClientPagination } from './claim-client-pagination';
export {
    getMyClaimsColumns,
    getMyClaimsActions,
    getApprovalClaimsColumns,
    getApprovalClaimsActions,
} from './claim-column-definitions';

export type { ClaimFormAction } from '@/types/claims';
