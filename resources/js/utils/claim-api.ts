import { router, type VisitOptions } from '@inertiajs/react';
import axios from '@/utils/axios-config';
import type { ClaimDetail, ClaimFormAction, ClaimFormValues } from '@/types/claims';

export type ClaimSubmitCallbacks = Partial<
    Pick<VisitOptions, 'onStart' | 'onFinish' | 'onSuccess' | 'onError' | 'onCancel'>
>;

function claimVisitOptions(callbacks?: ClaimSubmitCallbacks): VisitOptions {
    return {
        forceFormData: true,
        preserveScroll: true,
        ...callbacks,
    };
}

export function buildClaimFormData(
    values: ClaimFormValues,
    action: ClaimFormAction,
    file?: File | null,
    extra?: Record<string, string | number>
): FormData {
    const form = new FormData();
    form.append('claim_type', values.claim_type);
    form.append('claim_date', values.claim_date);
    form.append('amount', values.amount || '0');
    form.append('narration', values.narration || '');
    form.append('bill_no', values.bill_no || '');
    form.append('bill_date', values.bill_date || '');
    form.append('employee_remark', values.employee_remark || '');
    form.append('manager_remark', values.manager_remark || '');
    form.append('final_remark', values.final_remark || '');
    form.append('passed_amount', values.passed_amount || '');
    form.append('action', action);

    Object.entries(values.details || {}).forEach(([key, val]) => {
        form.append(`details[${key}]`, val ?? '');
    });

    if (file) {
        form.append('attachment', file);
    }

    if (extra) {
        Object.entries(extra).forEach(([k, v]) => form.append(k, String(v)));
    }

    return form;
}

export function submitNewClaim(
    values: ClaimFormValues,
    action: ClaimFormAction,
    file?: File | null,
    callbacks?: ClaimSubmitCallbacks
) {
    router.post(route('hr.claims.store'), buildClaimFormData(values, action, file), claimVisitOptions(callbacks));
}

/** @deprecated Used only by legacy claim-approvals page */
export function submitClaimUpdate(
    claimId: number,
    values: ClaimFormValues,
    action: ClaimFormAction,
    file?: File | null,
    extra?: Record<string, string | number>,
    callbacks?: ClaimSubmitCallbacks
) {
    const form = buildClaimFormData(values, action, file, extra);
    form.append('_method', 'PUT');
    router.post(route('hr.claims.update', claimId), form, claimVisitOptions(callbacks));
}

export async function fetchClaimDetail(claimId: number): Promise<{ claim: ClaimDetail }> {
    try {
        const { data } = await axios.get<{ claim: ClaimDetail }>(route('hr.claims.show', claimId), {
            headers: { Accept: 'application/json' },
        });
        return data;
    } catch (err: unknown) {
        const axiosErr = err as { response?: { status?: number; data?: { message?: string } } };
        const status = axiosErr.response?.status;
        const message =
            axiosErr.response?.data?.message ??
            (status === 403
                ? 'Permission denied'
                : status === 404
                  ? 'Claim not found'
                  : 'Failed to load claim');
        throw new Error(message);
    }
}
