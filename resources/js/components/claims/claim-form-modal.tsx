import { useEffect, useRef, useState } from 'react';
import { Loader2 } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslation } from 'react-i18next';
import type { ClaimFormValues, ClaimFormAction } from '@/types/claims';
import type { ClaimTypeKey } from '@/config/claims';
import { emptyClaimForm } from '@/types/claims';
import { getClaimTypeLabel } from '@/config/claims';
import { ClaimForm } from './claim-form';
import { ClaimCreateActions } from './claim-create-actions';

interface ClaimFormModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    claimType: ClaimTypeKey;
    onSubmit: (action: ClaimFormAction, values: ClaimFormValues, file?: File | null) => void;
    submitting?: boolean;
}

export function ClaimFormModal({
    open,
    onOpenChange,
    claimType,
    onSubmit,
    submitting = false,
}: ClaimFormModalProps) {
    const { t } = useTranslation();
    const [form, setForm] = useState<ClaimFormValues>(emptyClaimForm(claimType));
    const [file, setFile] = useState<File | null>(null);
    const wasOpenRef = useRef(false);

    useEffect(() => {
        if (!open) {
            wasOpenRef.current = false;
            return;
        }
        const justOpened = !wasOpenRef.current;
        wasOpenRef.current = true;
        if (justOpened) {
            setForm(emptyClaimForm(claimType));
            setFile(null);
        }
    }, [open, claimType]);

    const fire = (action: ClaimFormAction) => onSubmit(action, form, file);

    if (!open) {
        return null;
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (!submitting) onOpenChange(next);
            }}
        >
            <DialogContent
                modalId="claim-form-modal"
                className="max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl gap-0 overflow-hidden bg-background p-0 sm:w-full"
            >
                {submitting && (
                    <div className="absolute inset-0 z-20 flex items-center justify-center rounded-lg bg-background/60">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    </div>
                )}
                <DialogHeader className="shrink-0 border-b px-6 py-4 pr-12">
                    <DialogTitle>
                        {t('Add {{type}} Claim', { type: t(getClaimTypeLabel(claimType)) })}
                    </DialogTitle>
                </DialogHeader>

                <div className="relative max-h-[calc(90vh-11rem)] overflow-y-auto px-6 py-5">
                    <ClaimForm
                        claimType={claimType}
                        form={form}
                        onChange={setForm}
                        mode="create"
                        file={file}
                        onFileChange={setFile}
                    />
                </div>

                <DialogFooter className="shrink-0 flex-row gap-2 border-t bg-background px-6 py-3 sm:justify-end">
                    <ClaimCreateActions
                        loading={submitting}
                        onAction={fire}
                        className="w-full justify-end sm:w-auto"
                    />
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
