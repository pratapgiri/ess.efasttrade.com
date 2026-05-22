import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import type { ClaimDetail } from '@/types/claims';
import type { ClaimFormAction, ApprovalAction } from '@/types/claims';

export type FormActionHandler = (action: ClaimFormAction) => void;
export type ApprovalActionHandler = (action: ApprovalAction) => void;

interface ApprovalActionsProps {
    variant: 'employee' | 'approver';
    mode: 'create' | 'edit' | 'view';
    detail?: ClaimDetail | null;
    loading?: boolean;
    className?: string;
    onFormAction?: FormActionHandler;
    onApprovalAction?: ApprovalActionHandler;
    rejectDisabled?: boolean;
}

function ActionSpinner({ show }: { show: boolean }) {
    if (!show) return null;
    return <Loader2 className="mr-2 h-4 w-4 animate-spin" />;
}

export function ApprovalActions({
    variant,
    mode,
    detail,
    loading = false,
    className,
    onFormAction,
    onApprovalAction,
    rejectDisabled,
}: ApprovalActionsProps) {
    const { t } = useTranslation();

    if (mode === 'view') return null;

    if (variant === 'employee') {
        const showDraftForward = mode === 'create' || !!detail?.can_edit;
        return (
            <div className={cn('flex flex-wrap gap-2', className)}>
                {showDraftForward && (
                    <>
                        <Button type="button" variant="outline" disabled={loading} onClick={() => onFormAction?.('draft')}>
                            <ActionSpinner show={loading} />
                            {t('Save Draft')}
                        </Button>
                        <Button type="button" disabled={loading} onClick={() => onFormAction?.('forward')}>
                            <ActionSpinner show={loading} />
                            {t('Forward')}
                        </Button>
                    </>
                )}
                {detail?.can_cancel && (
                    <Button type="button" variant="ghost" disabled={loading} onClick={() => onFormAction?.('cancel')}>
                        {t('Cancel')}
                    </Button>
                )}
            </div>
        );
    }

    return (
        <div className={cn('flex flex-wrap gap-2', className)}>
            {detail?.can_forward && !detail?.can_approve && (
                <Button type="button" disabled={loading} onClick={() => onApprovalAction?.('forward')}>
                    <ActionSpinner show={loading} />
                    {t('Forward')}
                </Button>
            )}
            {detail?.can_reject && (
                <Button
                    type="button"
                    variant="destructive"
                    disabled={rejectDisabled || loading}
                    onClick={() => onApprovalAction?.('reject')}
                >
                    <ActionSpinner show={loading} />
                    {t('Reject')}
                </Button>
            )}
            {detail?.can_approve && (
                <Button
                    type="button"
                    className="bg-emerald-600 hover:bg-emerald-700"
                    disabled={loading}
                    onClick={() => onApprovalAction?.('approve')}
                >
                    <ActionSpinner show={loading} />
                    {t('Approve')}
                </Button>
            )}
        </div>
    );
}
