import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import type { ClaimDetail, ClaimFormAction, ClaimFormValues } from '@/types/claims';
import { getClaimTypeLabel } from '@/config/claims';
import { ClaimStatusBadge } from './claim-status-badge';
import { ClaimAttachmentPreview } from './claim-attachment-preview';
import { ClaimAmountCard } from './ClaimAmountCard';
import { ClaimForm } from './claim-form';
import { claimDetailToFormValues } from '@/utils/claim-form';

interface ClaimViewModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    detail: ClaimDetail | null;
    loading?: boolean;
    onFormAction?: (action: ClaimFormAction, values: ClaimFormValues, file?: File | null) => void;
    submitting?: boolean;
}

function ReadonlyField({ label, value }: { label: string; value?: string | null }) {
    return (
        <div className="space-y-1">
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="text-sm text-foreground">{value?.trim() ? value : '—'}</p>
        </div>
    );
}

export function ClaimViewModal({
    open,
    onOpenChange,
    detail,
    loading = false,
    onFormAction,
    submitting = false,
}: ClaimViewModalProps) {
    const { t } = useTranslation();
    const [editForm, setEditForm] = useState<ClaimFormValues | null>(null);
    const [file, setFile] = useState<File | null>(null);

    useEffect(() => {
        if (detail?.is_editable_employee) {
            setEditForm(claimDetailToFormValues(detail));
            setFile(null);
        } else {
            setEditForm(null);
            setFile(null);
        }
    }, [detail]);

    if (!open) return null;

    const isEditMode = !!(detail?.is_editable_employee && editForm);
    const d = detail?.details ?? {};

    const travelTotal =
        detail?.claim_type === 'intercity_travel'
            ? (parseFloat(d.ticket_amount || '0') || 0) +
              (parseFloat(d.hotel_amount || '0') || 0) +
              (parseFloat(d.food_amount || '0') || 0)
            : 0;

    const fireAction = (action: ClaimFormAction) => {
        onFormAction?.(action, editForm!, action === 'cancel' ? null : file);
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !loading && !submitting && onOpenChange(next)}>
            <DialogContent
                modalId="claim-view-modal"
                className="max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl gap-0 overflow-hidden bg-background p-0 sm:w-full"
            >
                {(loading || submitting) && (
                    <div className="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-background/60">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    </div>
                )}

                <DialogHeader className="shrink-0 border-b px-6 py-4 pr-12">
                    <div className="flex flex-wrap items-center gap-2">
                        <DialogTitle>
                            {isEditMode ? t('Edit Claim') : t('View Claim')}
                        </DialogTitle>
                        {detail?.status && <ClaimStatusBadge status={detail.status} />}
                    </div>
                    {detail?.claim_no && (
                        <DialogDescription>
                            {t('Claim No')}: {detail.claim_no}
                            {detail.claim_type && (
                                <span className="ml-2 text-muted-foreground">
                                    · {t(getClaimTypeLabel(detail.claim_type))}
                                </span>
                            )}
                        </DialogDescription>
                    )}
                </DialogHeader>

                <div className="relative max-h-[calc(90vh-11rem)] overflow-y-auto px-6 py-5">
                    {loading ? (
                        <div className="flex min-h-[200px] items-center justify-center py-12">
                            <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        </div>
                    ) : detail ? (
                        isEditMode && editForm ? (
                            /* ── EDIT MODE ── */
                            <div className="space-y-4">
                                {/* Editable claim fields */}
                                <ClaimForm
                                    claimType={detail.claim_type}
                                    form={editForm}
                                    onChange={setEditForm}
                                    mode="create"
                                    file={file}
                                    onFileChange={setFile}
                                />

                                {/* Read-only approval info shown for context */}
                                {(detail.manager_remark || detail.final_remark || detail.passed_amount) && (
                                    <section className="rounded-lg border bg-muted/30 p-4">
                                        <h4 className="mb-4 text-sm font-semibold text-foreground">
                                            {t('Approval Information')}
                                        </h4>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {detail.manager_remark && (
                                                <div className="sm:col-span-2">
                                                    <ReadonlyField
                                                        label={t('Manager Remarks')}
                                                        value={detail.manager_remark}
                                                    />
                                                </div>
                                            )}
                                            {detail.final_remark && (
                                                <div className="sm:col-span-2">
                                                    <ReadonlyField
                                                        label={t('Final Remarks')}
                                                        value={detail.final_remark}
                                                    />
                                                </div>
                                            )}
                                            {detail.passed_amount && (
                                                <ReadonlyField
                                                    label={t('Passed Amount')}
                                                    value={detail.passed_amount}
                                                />
                                            )}
                                        </div>
                                    </section>
                                )}
                            </div>
                        ) : (
                            /* ── READ-ONLY MODE ── */
                            <div className="space-y-6">
                                <section className="rounded-lg border bg-muted/30 p-4">
                                    <h4 className="mb-4 text-sm font-semibold">{t('Claim Details')}</h4>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <ReadonlyField label={t('Date')} value={detail.claim_date} />
                                        {detail.claim_type === 'expense' && (
                                            <ReadonlyField label={t('Amount')} value={detail.amount} />
                                        )}
                                        {detail.claim_type === 'expense' && (
                                            <div className="sm:col-span-2">
                                                <ReadonlyField label={t('Expense Details')} value={detail.narration} />
                                            </div>
                                        )}
                                        {detail.claim_type === 'expense' && (
                                            <>
                                                <ReadonlyField label={t('Bill No')} value={detail.bill_no} />
                                                <ReadonlyField label={t('Bill Date')} value={detail.bill_date} />
                                            </>
                                        )}
                                        {detail.claim_type === 'local_conveyance' && (
                                            <>
                                                <ReadonlyField label={t('Amount')} value={detail.amount} />
                                                <ReadonlyField label={t('From Location')} value={d.from_location} />
                                                <ReadonlyField label={t('To Location')} value={d.to_location} />
                                                <ReadonlyField label={t('Distance (KM)')} value={d.distance} />
                                                <ReadonlyField label={t('Purpose')} value={detail.narration} />
                                            </>
                                        )}
                                        {detail.claim_type === 'intercity_travel' && (
                                            <>
                                                <ReadonlyField label={t('From City')} value={d.from_city} />
                                                <ReadonlyField label={t('To City')} value={d.to_city} />
                                                <ReadonlyField label={t('Travel Mode')} value={d.travel_mode} />
                                                <ReadonlyField label={t('Ticket Amount')} value={d.ticket_amount} />
                                                <ReadonlyField label={t('Hotel Amount')} value={d.hotel_amount} />
                                                <ReadonlyField label={t('Food Amount')} value={d.food_amount} />
                                            </>
                                        )}
                                    </div>
                                    {detail.claim_type === 'intercity_travel' && (
                                        <div className="mt-4">
                                            <ClaimAmountCard
                                                label={t('Total Amount')}
                                                amount={travelTotal}
                                                variant="claimed"
                                            />
                                        </div>
                                    )}
                                </section>

                                <section className="rounded-lg border p-4">
                                    <h4 className="mb-3 text-sm font-semibold">{t('Attachment')}</h4>
                                    <ClaimAttachmentPreview
                                        url={detail.attachment}
                                        fileName={detail.attachment_name}
                                        isImage={detail.attachment_is_image}
                                    />
                                </section>

                                {detail.employee_remark && (
                                    <section className="rounded-lg border bg-muted/30 p-4">
                                        <ReadonlyField
                                            label={t('Employee Remark')}
                                            value={detail.employee_remark}
                                        />
                                    </section>
                                )}

                                {(detail.manager_remark || detail.final_remark || detail.passed_amount) && (
                                    <section className="rounded-lg border bg-muted/30 p-4">
                                        <h4 className="mb-4 text-sm font-semibold">{t('Approval Information')}</h4>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {detail.manager_remark && (
                                                <div className="sm:col-span-2">
                                                    <ReadonlyField
                                                        label={t('Manager Remarks')}
                                                        value={detail.manager_remark}
                                                    />
                                                </div>
                                            )}
                                            {detail.final_remark && (
                                                <div className="sm:col-span-2">
                                                    <ReadonlyField
                                                        label={t('Final Remarks')}
                                                        value={detail.final_remark}
                                                    />
                                                </div>
                                            )}
                                            {detail.passed_amount && (
                                                <ReadonlyField
                                                    label={t('Passed Amount')}
                                                    value={detail.passed_amount}
                                                />
                                            )}
                                        </div>
                                    </section>
                                )}

                                <section className="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
                                    <ReadonlyField label={t('Status')} value={detail.status} />
                                    <ReadonlyField label={t('Pending At')} value={detail.pending_at} />
                                </section>
                            </div>
                        )
                    ) : (
                        <p className="py-8 text-center text-sm text-muted-foreground">{t('No claim data')}</p>
                    )}
                </div>

                <DialogFooter className="shrink-0 gap-2 border-t bg-background px-6 py-3 sm:flex-row sm:justify-end">
                    {isEditMode ? (
                        <>
                            {detail?.can_cancel && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    disabled={submitting}
                                    className="text-destructive hover:text-destructive"
                                    onClick={() => fireAction('cancel')}
                                >
                                    {t('Cancel Claim')}
                                </Button>
                            )}
                            <div className="flex flex-1 justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={submitting}
                                    onClick={() => fireAction('draft')}
                                >
                                    {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    {t('Save Draft')}
                                </Button>
                                {detail?.can_forward && (
                                    <Button
                                        type="button"
                                        disabled={submitting}
                                        onClick={() => fireAction('forward')}
                                    >
                                        {submitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        {t('Forward')}
                                    </Button>
                                )}
                            </div>
                        </>
                    ) : (
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            {t('Close')}
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
