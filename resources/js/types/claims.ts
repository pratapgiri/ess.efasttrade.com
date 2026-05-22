import type { ClaimTypeKey, ClaimVisibilityConfig } from '@/config/claims';

export type ClaimType = ClaimTypeKey;
export type { ClaimVisibilityConfig };

export type ClaimStatus = 'draft' | 'pending' | 'approved' | 'rejected' | 'cancelled';

export type WorkflowLogItem = {
    action: string;
    from_pending_at?: string;
    to_pending_at?: string;
    performer?: string;
    remarks?: string;
    created_at: string;
};

export type ClaimListItem = {
    id: number;
    claim_no: string;
    date: string;
    claim_type: ClaimType;
    amount: number;
    status: ClaimStatus;
    pending_at: string;
    narration: string;
    employee_name?: string;
    employee_id?: number;
    pending_since?: string;
    has_attachment?: boolean;
    attachment_name?: string;
};

export type ClaimFormValues = {
    claim_type: ClaimType;
    claim_date: string;
    amount: string;
    narration: string;
    bill_no: string;
    bill_date: string;
    employee_remark: string;
    manager_remark: string;
    final_remark: string;
    passed_amount: string;
    attachment?: string;
    attachment_download?: string;
    attachment_name?: string;
    attachment_is_image?: boolean;
    details: Record<string, string>;
};

export type ClaimDetail = ClaimFormValues & {
    id: number;
    claim_no: string;
    status: ClaimStatus;
    pending_at: string;
    employee_name: string;
    can_edit?: boolean;
    is_editable_employee?: boolean;
    can_edit_manager_remark?: boolean;
    can_edit_final_remark?: boolean;
    can_edit_passed_amount?: boolean;
    final_remark_readonly?: boolean;
    can_forward?: boolean;
    can_cancel?: boolean;
    can_reject?: boolean;
    can_approve?: boolean;
    workflow_logs?: WorkflowLogItem[];
    previous_steps?: { serial_number: number; emp_type: string }[];
};

export type WorkflowLevel = {
    id: string;
    serial_number: number;
    emp_type: string;
    approver_user_id: string;
    department_id: string;
};

export type WorkflowListItem = {
    id: number;
    workflow_name: string;
    claim_type: ClaimType;
    company_name: string;
    total_levels: number;
    status: 'active' | 'inactive';
    levels: WorkflowLevel[];
};

export type ClaimFormModalMode = 'create' | 'edit' | 'view';
export type ClaimFormAction = 'draft' | 'forward' | 'reject' | 'approve' | 'cancel';
export type ApprovalAction = 'forward' | 'reject' | 'approve';

export function emptyClaimForm(claimType: ClaimType = 'expense'): ClaimFormValues {
    return {
        claim_type: claimType,
        claim_date: new Date().toISOString().slice(0, 10),
        amount: '',
        narration: '',
        bill_no: '',
        bill_date: '',
        employee_remark: '',
        manager_remark: '',
        final_remark: '',
        passed_amount: '',
        attachment: '',
        details: {},
    };
}

export { DEFAULT_VISIBILITY, getClaimTypeLabel } from '@/config/claims';
