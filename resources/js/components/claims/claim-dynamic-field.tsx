import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Upload, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ClaimAttachmentPreview } from './claim-attachment-preview';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { cn } from '@/lib/utils';
import type { ClaimFieldSchema } from '@/config/claims/claim-fields';
import type { ClaimFormValues } from '@/types/claims';

interface ClaimDynamicFieldProps {
    field: ClaimFieldSchema;
    form: ClaimFormValues;
    disabled?: boolean;
    onChange: (form: ClaimFormValues) => void;
    onFileChange?: (file: File | null) => void;
    fileName?: string | null;
    attachmentUrl?: string | null;
    attachmentDownloadUrl?: string | null;
    attachmentIsImage?: boolean;
    previewFile?: File | null;
}

function readValue(form: ClaimFormValues, field: ClaimFieldSchema): string {
    if (field.formKey) return String(form[field.formKey] ?? '');
    if (field.detailKey) return String(form.details[field.detailKey] ?? '');
    return '';
}

function writeValue(form: ClaimFormValues, field: ClaimFieldSchema, value: string): ClaimFormValues {
    if (field.formKey) {
        const next = { ...form, [field.formKey]: value };
        if (field.detailKey && field.formKey === 'narration') {
            next.details = { ...next.details, [field.detailKey]: value };
        }
        return next;
    }
    if (field.detailKey) {
        return { ...form, details: { ...form.details, [field.detailKey]: value } };
    }
    return form;
}

export function ClaimDynamicField({
    field,
    form,
    disabled,
    onChange,
    onFileChange,
    fileName,
    attachmentUrl,
    attachmentDownloadUrl,
    attachmentIsImage,
    previewFile,
}: ClaimDynamicFieldProps) {
    const { t } = useTranslation();
    const value = readValue(form, field);

    const handleChange = (next: string) => onChange(writeValue(form, field, next));

    if (field.type === 'file') {
        const accept = field.accept ?? '.jpg,.jpeg,.png,.pdf';
        const maxBytes = 5 * 1024 * 1024;

        const handleFileSelect = (selected: File | null) => {
            if (!selected) {
                onFileChange?.(null);
                return;
            }
            const allowed = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!allowed.includes(selected.type)) {
                toast.error(t('Only JPG, PNG, and PDF files are allowed.'));
                return;
            }
            if (selected.size > maxBytes) {
                toast.error(t('File size must not exceed 5MB.'));
                return;
            }
            onFileChange?.(selected);
        };

        return (
            <div className={cn('space-y-1.5', field.gridSpan === 2 && 'sm:col-span-2')}>
                <Label>
                    {t(field.label)}
                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                        ({t('JPG, PNG, PDF — max 5MB')})
                    </span>
                </Label>
                {previewFile && (
                    <div className="relative">
                        <ClaimAttachmentPreview
                            previewFile={previewFile}
                            fileName={previewFile.name}
                            isImage={previewFile.type.startsWith('image/')}
                        />
                        {!disabled && (
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                className="absolute right-2 top-2 h-7 gap-1 px-2"
                                onClick={() => onFileChange?.(null)}
                            >
                                <X className="h-3.5 w-3.5" />
                                {t('Remove')}
                            </Button>
                        )}
                    </div>
                )}
                {!disabled && (
                    <label
                        className={cn(
                            'flex cursor-pointer flex-col items-center justify-center rounded-md border border-dashed border-muted-foreground/30 bg-background p-4 transition-colors hover:border-primary/40 hover:bg-muted/40',
                            disabled && 'pointer-events-none opacity-60'
                        )}
                    >
                        <Upload className="mb-2 h-5 w-5 text-muted-foreground" />
                        <span className="max-w-full truncate px-2 text-center text-xs text-muted-foreground">
                            {previewFile?.name ?? t('Click to upload bill or receipt')}
                        </span>
                        <Input
                            type="file"
                            className="hidden"
                            accept={accept}
                            disabled={disabled}
                            onChange={(e) => handleFileSelect(e.target.files?.[0] ?? null)}
                        />
                    </label>
                )}
            </div>
        );
    }

    const spanClass =
        field.gridSpan === 2 ? 'sm:col-span-2' : field.gridSpan === 3 ? 'sm:col-span-3' : '';

    return (
        <div className={cn('space-y-1.5', spanClass)}>
            <Label>
                {t(field.label)}
                {field.required && <span className="text-destructive"> *</span>}
            </Label>
            {field.type === 'textarea' ? (
                <Textarea
                    value={value}
                    disabled={disabled}
                    rows={3}
                    placeholder={field.placeholder}
                    onChange={(e) => handleChange(e.target.value)}
                />
            ) : (
                <Input
                    type={field.type === 'date' ? 'date' : field.type === 'number' || field.type === 'currency' ? 'number' : 'text'}
                    step={field.type === 'currency' || field.type === 'number' ? '0.01' : undefined}
                    value={value}
                    disabled={disabled}
                    placeholder={field.placeholder}
                    onChange={(e) => handleChange(e.target.value)}
                />
            )}
        </div>
    );
}
