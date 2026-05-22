import { emptyClaimForm, type ClaimDetail, type ClaimFormValues } from '@/types/claims';

/** HTML date inputs require YYYY-MM-DD */
export function toDateInputValue(value?: string | null): string {
    if (!value) {
        return '';
    }
    const trimmed = String(value).trim();

    return trimmed.length >= 10 ? trimmed.slice(0, 10) : trimmed;
}

export function formatAmountInput(value?: string | number | null): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const n = typeof value === 'number' ? value : Number.parseFloat(String(value).replace(/,/g, ''));

    if (Number.isNaN(n)) {
        return '';
    }

    return String(n);
}

export function claimDetailToFormValues(detail: ClaimDetail): ClaimFormValues {
    const base = emptyClaimForm(detail.claim_type);
    const details = { ...base.details, ...(detail.details ?? {}) };

    return {
        ...base,
        claim_type: detail.claim_type,
        claim_date: toDateInputValue(detail.claim_date),
        amount: formatAmountInput(detail.amount),
        narration: detail.narration ?? details.expense_details ?? '',
        bill_no: detail.bill_no ?? details.bill_no ?? '',
        bill_date: toDateInputValue(detail.bill_date ?? details.bill_date),
        employee_remark: detail.employee_remark ?? '',
        manager_remark: detail.manager_remark ?? '',
        final_remark: detail.final_remark ?? '',
        passed_amount: detail.passed_amount ?? '',
        attachment: detail.attachment ?? '',
        attachment_download: detail.attachment_download,
        attachment_name: detail.attachment_name,
        attachment_is_image: detail.attachment_is_image,
        details,
    };
}
