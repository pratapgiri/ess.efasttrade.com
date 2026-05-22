import { useEffect, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { FileText, Loader2 } from 'lucide-react';
import { ClaimAttachmentPreview } from './claim-attachment-preview';
import { useTranslation } from 'react-i18next';
import type { ClaimDetail, ApprovalAction } from '@/types/claims';
import { getClaimTypeLabel } from '@/config/claims';
import { ClaimStatusBadge } from './claim-status-badge';
import { WorkflowTimeline } from './workflow-timeline';
import { ClaimAmountCard } from './ClaimAmountCard';
import { ApprovalActions } from './approval-actions';

interface ClaimApprovalModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    claim: ClaimDetail | null;
    previousSteps?: { serial_number: number; emp_type: string }[];
    onAction?: (
        action: ApprovalAction,
        payload: { manager_remark: string; final_remark: string; passed_amount: string; reject_to_step: string }
    ) => void;
    submitting?: boolean;
}

export function ClaimApprovalModal({
    open,
    onOpenChange,
    claim,
    previousSteps = [],
    onAction,
    submitting = false,
}: ClaimApprovalModalProps) {
    const { t } = useTranslation();
    const [managerRemark, setManagerRemark] = useState('');
    const [finalRemark, setFinalRemark] = useState('');
    const [passedAmount, setPassedAmount] = useState('');
    const [rejectToStep, setRejectToStep] = useState('');

    useEffect(() => {
        if (claim && open) {
            setManagerRemark(claim.manager_remark ?? '');
            setFinalRemark(claim.final_remark ?? '');
            setPassedAmount(claim.passed_amount ?? claim.amount ?? '');
            setRejectToStep('');
        }
    }, [claim, open]);

    if (!claim || !open) return null;

    const submit = (action: ApprovalAction) => {
        onAction?.(action, {
            manager_remark: managerRemark,
            final_remark: finalRemark,
            passed_amount: passedAmount,
            reject_to_step: rejectToStep,
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!submitting) onOpenChange(next);
            }}
        >
            <DialogContent
                modalId="claim-approval-modal"
                className="max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl gap-0 overflow-hidden bg-background p-0 sm:w-full"
            >
                {submitting && (
                    <div className="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-background/60">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    </div>
                )}
                <DialogHeader className="shrink-0 border-b px-6 py-4 pr-12">
                    <div className="flex flex-wrap items-center gap-2">
                        <DialogTitle>{t('Review Claim')}</DialogTitle>
                        <ClaimStatusBadge status={claim.status} />
                    </div>
                    <DialogDescription>
                        {claim.claim_no} · {t(getClaimTypeLabel(claim.claim_type))} · {claim.employee_name}
                    </DialogDescription>
                </DialogHeader>

                <div className="max-h-[calc(90vh-11rem)] overflow-y-auto">
                    <div className="grid gap-6 px-6 py-4 lg:grid-cols-2">
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-3">
                                <ClaimAmountCard label="Claimed Amount" amount={claim.amount} variant="claimed" />
                                {claim.passed_amount && (
                                    <ClaimAmountCard label="Passed Amount" amount={claim.passed_amount} variant="passed" />
                                )}
                            </div>
                            <dl className="grid gap-2 text-sm">
                                <div className="flex justify-between gap-2 border-b py-2">
                                    <dt className="text-muted-foreground">{t('Date')}</dt>
                                    <dd className="font-medium">{claim.claim_date}</dd>
                                </div>
                                <div className="flex justify-between gap-2 border-b py-2">
                                    <dt className="text-muted-foreground">{t('Pending At')}</dt>
                                    <dd className="font-medium capitalize">{claim.pending_at}</dd>
                                </div>
                                <div className="flex justify-between gap-2 border-b py-2">
                                    <dt className="text-muted-foreground">{t('Narration')}</dt>
                                    <dd className="max-w-[60%] text-right font-medium">{claim.narration || '—'}</dd>
                                </div>
                            </dl>
                            <div className="rounded-lg border bg-muted/30 p-4">
                                <Label className="mb-2 flex items-center gap-2">
                                    <FileText className="h-4 w-4" />
                                    {t('Attachment')}
                                </Label>
                                <ClaimAttachmentPreview
                                    url={claim.attachment}
                                    downloadUrl={claim.attachment_download}
                                    fileName={claim.attachment_name}
                                    isImage={claim.attachment_is_image}
                                />
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <h4 className="mb-3 text-sm font-medium">{t('Workflow Timeline')}</h4>
                                <WorkflowTimeline
                                    logs={claim.workflow_logs}
                                    currentPendingAt={claim.pending_at}
                                />
                            </div>
                            <Separator />
                            <div className="space-y-4">
                                <h4 className="text-sm font-medium">{t('Approval')}</h4>
                                {claim.can_edit_manager_remark && (
                                    <div className="space-y-1.5">
                                        <Label>{t('Manager Remarks')}</Label>
                                        <Textarea value={managerRemark} onChange={(e) => setManagerRemark(e.target.value)} rows={2} />
                                    </div>
                                )}
                                {claim.can_edit_final_remark && (
                                    <div className="space-y-1.5">
                                        <Label>{t('Final Remarks')}</Label>
                                        <Textarea value={finalRemark} onChange={(e) => setFinalRemark(e.target.value)} rows={2} />
                                    </div>
                                )}
                                {claim.can_edit_passed_amount && (
                                    <div className="space-y-1.5">
                                        <Label>{t('Passed Amount')}</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            value={passedAmount}
                                            onChange={(e) => setPassedAmount(e.target.value)}
                                        />
                                    </div>
                                )}
                                {claim.can_reject && previousSteps.length > 0 && (
                                    <div className="space-y-1.5">
                                        <Label>{t('Reject To')}</Label>
                                        <Select value={rejectToStep} onValueChange={setRejectToStep}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('Select previous level')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {previousSteps.map((step) => (
                                                    <SelectItem key={step.serial_number} value={String(step.serial_number)}>
                                                        {step.emp_type} ({t('Level')} {step.serial_number})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter className="flex-row justify-end gap-2 border-t bg-background px-6 py-3 sm:justify-end">
                    <ApprovalActions
                        variant="approver"
                        mode="edit"
                        detail={claim}
                        loading={submitting}
                        className="w-full justify-end"
                        rejectDisabled={claim.can_reject && previousSteps.length > 0 && !rejectToStep}
                        onApprovalAction={submit}
                    />
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
