import type { ClaimTypeKey } from './claim-types';

export type ClaimFieldType = 'text' | 'textarea' | 'number' | 'currency' | 'date' | 'select' | 'file';

export interface ClaimFieldSchema {
    name: string;
    label: string;
    type: ClaimFieldType;
    required?: boolean;
    section?: 'details' | 'attachment' | 'remarks' | 'approval';
    /** Bind to top-level form key instead of details */
    formKey?: 'claim_date' | 'amount' | 'narration' | 'bill_no' | 'bill_date' | 'employee_remark' | 'manager_remark' | 'final_remark' | 'passed_amount';
    /** Store in form.details */
    detailKey?: string;
    placeholder?: string;
    gridSpan?: 1 | 2 | 3;
    accept?: string;
    options?: { value: string; label: string }[];
    readOnly?: boolean;
    showWhen?: 'create' | 'edit' | 'view' | 'approval';
}

export const expenseFields: ClaimFieldSchema[] = [
    { name: 'expense_date', label: 'Expense Date', type: 'date', required: true, section: 'details', formKey: 'claim_date', gridSpan: 1 },
    { name: 'amount', label: 'Amount', type: 'currency', required: true, section: 'details', formKey: 'amount', gridSpan: 1 },
    { name: 'expense_details', label: 'Expense Details', type: 'textarea', required: true, section: 'details', formKey: 'narration', gridSpan: 2 },
    { name: 'bill_no', label: 'Bill No', type: 'text', section: 'details', formKey: 'bill_no', gridSpan: 1 },
    { name: 'bill_date', label: 'Bill Date', type: 'date', section: 'details', formKey: 'bill_date', gridSpan: 1 },
    { name: 'attachment', label: 'Attachment', type: 'file', section: 'attachment', accept: '.jpg,.jpeg,.png,.pdf', gridSpan: 2 },
    { name: 'employee_remark', label: 'Employee Remark', type: 'textarea', section: 'remarks', formKey: 'employee_remark', gridSpan: 2 },
];

export const conveyanceFields: ClaimFieldSchema[] = [
    { name: 'claim_date', label: 'Date', type: 'date', required: true, section: 'details', formKey: 'claim_date', gridSpan: 1 },
    { name: 'amount', label: 'Amount', type: 'currency', required: true, section: 'details', formKey: 'amount', gridSpan: 1 },
    { name: 'from_location', label: 'From Location', type: 'text', required: true, section: 'details', detailKey: 'from_location', gridSpan: 1 },
    { name: 'to_location', label: 'To Location', type: 'text', required: true, section: 'details', detailKey: 'to_location', gridSpan: 1 },
    { name: 'distance', label: 'Distance (KM)', type: 'number', section: 'details', detailKey: 'distance', gridSpan: 1 },
    { name: 'purpose', label: 'Purpose', type: 'text', required: true, section: 'details', detailKey: 'purpose', formKey: 'narration', gridSpan: 1 },
    { name: 'attachment', label: 'Attachment', type: 'file', section: 'attachment', gridSpan: 2 },
    { name: 'employee_remark', label: 'Employee Remark', type: 'textarea', section: 'remarks', formKey: 'employee_remark', gridSpan: 2 },
];

export const travelFields: ClaimFieldSchema[] = [
    { name: 'travel_date', label: 'Travel Date', type: 'date', required: true, section: 'details', formKey: 'claim_date', gridSpan: 1 },
    { name: 'travel_mode', label: 'Travel Mode', type: 'text', section: 'details', detailKey: 'travel_mode', gridSpan: 1 },
    { name: 'from_city', label: 'From City', type: 'text', required: true, section: 'details', detailKey: 'from_city', gridSpan: 1 },
    { name: 'to_city', label: 'To City', type: 'text', required: true, section: 'details', detailKey: 'to_city', gridSpan: 1 },
    { name: 'ticket_amount', label: 'Ticket Amount', type: 'currency', section: 'details', detailKey: 'ticket_amount', gridSpan: 1 },
    { name: 'hotel_amount', label: 'Hotel Amount', type: 'currency', section: 'details', detailKey: 'hotel_amount', gridSpan: 1 },
    { name: 'food_amount', label: 'Food Amount', type: 'currency', section: 'details', detailKey: 'food_amount', gridSpan: 1 },
    { name: 'attachment', label: 'Attachment', type: 'file', section: 'attachment', gridSpan: 2 },
    { name: 'employee_remark', label: 'Employee Remark', type: 'textarea', section: 'remarks', formKey: 'employee_remark', gridSpan: 2 },
];

export const approvalFields: ClaimFieldSchema[] = [
    { name: 'manager_remark', label: 'Manager Remarks', type: 'textarea', section: 'approval', formKey: 'manager_remark', gridSpan: 2, showWhen: 'approval' },
    { name: 'final_remark', label: 'Final Remarks', type: 'textarea', section: 'approval', formKey: 'final_remark', gridSpan: 2, showWhen: 'approval' },
    { name: 'passed_amount', label: 'Passed Amount', type: 'currency', section: 'approval', formKey: 'passed_amount', gridSpan: 1, showWhen: 'approval' },
];

export const CLAIM_FIELDS_BY_TYPE: Record<ClaimTypeKey, ClaimFieldSchema[]> = {
    expense: expenseFields,
    local_conveyance: conveyanceFields,
    intercity_travel: travelFields,
};

export function getFieldsForClaimType(type: ClaimTypeKey, includeApproval = false): ClaimFieldSchema[] {
    const base = CLAIM_FIELDS_BY_TYPE[type] ?? expenseFields;
    return includeApproval ? [...base, ...approvalFields] : base;
}

export function getFieldsBySection(fields: ClaimFieldSchema[], section: ClaimFieldSchema['section']) {
    return fields.filter((f) => f.section === section);
}
