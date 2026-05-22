import { useEffect, useState } from 'react';
import { ExternalLink, FileText } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

function isImageFile(name?: string | null, url?: string | null): boolean {
    const value = (name ?? url ?? '').toLowerCase();

    return /\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i.test(value);
}

interface ClaimAttachmentPreviewProps {
    url?: string | null;
    downloadUrl?: string | null;
    fileName?: string | null;
    previewFile?: File | null;
    isImage?: boolean;
    className?: string;
}

export function ClaimAttachmentPreview({
    url,
    downloadUrl,
    fileName,
    previewFile,
    isImage,
    className,
}: ClaimAttachmentPreviewProps) {
    const { t } = useTranslation();
    const [previewSrc, setPreviewSrc] = useState<string | null>(null);
    const [imageError, setImageError] = useState(false);

    const treatAsImage = isImage ?? isImageFile(fileName, url);
    const viewHref = url ?? null;

    useEffect(() => {
        setImageError(false);
        if (previewFile) {
            const objectUrl = URL.createObjectURL(previewFile);
            setPreviewSrc(objectUrl);

            return () => URL.revokeObjectURL(objectUrl);
        }
        setPreviewSrc(url ?? null);
    }, [previewFile, url]);

    if (!url && !previewFile && !fileName) {
        return <p className="text-sm text-muted-foreground">{t('No attachment')}</p>;
    }

    return (
        <div className={cn('space-y-2', className)}>
            {treatAsImage && previewSrc && !imageError && (
                <div className="overflow-hidden rounded-md border bg-muted/20 p-2">
                    <img
                        src={previewSrc}
                        alt={fileName ?? t('Attachment')}
                        className="mx-auto max-h-64 w-full object-contain"
                        onError={() => setImageError(true)}
                    />
                </div>
            )}
            {viewHref && treatAsImage && !imageError && (
                <a
                    href={viewHref}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                >
                    {t('View full size')}
                    <ExternalLink className="h-3.5 w-3.5 shrink-0" />
                </a>
            )}
            {viewHref && (!treatAsImage || imageError) && (
                <a
                    href={viewHref}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                >
                    <FileText className="h-4 w-4 shrink-0" />
                    {fileName ?? t('View attachment')}
                    <ExternalLink className="h-3.5 w-3.5 shrink-0" />
                </a>
            )}
            {downloadUrl && !treatAsImage && (
                <a
                    href={downloadUrl}
                    className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-primary hover:underline"
                >
                    {t('Download')}
                </a>
            )}
            {!viewHref && !downloadUrl && fileName && (
                <p className="text-sm text-muted-foreground">{fileName}</p>
            )}
        </div>
    );
}
