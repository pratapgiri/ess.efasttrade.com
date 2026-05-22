import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';
import {
    getFieldsBySection,
    getFieldsForClaimType,
    type ClaimFieldSchema,
} from '@/config/claims/claim-fields';
import type { ClaimTypeKey } from '@/config/claims';
import type { ClaimFormValues } from '@/types/claims';
import { ClaimDynamicField } from './claim-dynamic-field';
import { ClaimAmountCard } from './ClaimAmountCard';

interface ClaimFormProps {
    claimType: ClaimTypeKey;
    form: ClaimFormValues;
    onChange: (form: ClaimFormValues) => void;
    mode: 'create';
    file?: File | null;
    onFileChange?: (file: File | null) => void;
}

export function ClaimForm({ claimType, form, onChange, mode, file, onFileChange }: ClaimFormProps) {
    const { t } = useTranslation();
    const fields = useMemo(() => getFieldsForClaimType(claimType, false), [claimType]);

    const travelTotal = useMemo(() => {
        if (claimType !== 'intercity_travel') return 0;
        const ticket = parseFloat(form.details.ticket_amount || '0') || 0;
        const hotel = parseFloat(form.details.hotel_amount || '0') || 0;
        const food = parseFloat(form.details.food_amount || '0') || 0;
        return ticket + hotel + food;
    }, [claimType, form.details]);

    const sections: { key: ClaimFieldSchema['section']; title: string }[] = [
        { key: 'details', title: 'Claim Details' },
        { key: 'attachment', title: 'Attachment Upload' },
        { key: 'remarks', title: 'Employee Remarks' },
    ];

    return (
        <div className="space-y-4">
            {sections.map((section) => {
                const sectionFields = getFieldsBySection(fields, section.key);
                if (sectionFields.length === 0) return null;

                return (
                    <section
                        key={section.key}
                        className={cn(
                            'rounded-lg border bg-muted/30 p-4',
                            section.key === 'attachment' && 'bg-background'
                        )}
                    >
                        <h4 className="mb-4 text-sm font-semibold text-foreground">{t(section.title)}</h4>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {sectionFields.map((field) => (
                                <ClaimDynamicField
                                    key={field.name}
                                    field={field}
                                    form={form}
                                    disabled={false}
                                    onChange={onChange}
                                    onFileChange={field.type === 'file' ? onFileChange : undefined}
                                    fileName={file?.name ?? null}
                                    previewFile={field.type === 'file' ? file : undefined}
                                />
                            ))}
                        </div>
                        {claimType === 'intercity_travel' && section.key === 'details' && (
                            <div className="mt-4">
                                <ClaimAmountCard label="Total Amount" amount={travelTotal} variant="claimed" />
                            </div>
                        )}
                    </section>
                );
            })}
        </div>
    );
}
