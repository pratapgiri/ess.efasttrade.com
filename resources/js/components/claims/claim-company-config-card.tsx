import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { COMPANY_CONFIG_TOGGLES } from '@/config/claims/claim-company-config';
import type { ClaimVisibilityConfig } from '@/config/claims';

interface ClaimCompanyConfigCardProps {
    config: ClaimVisibilityConfig;
    onChange: (config: ClaimVisibilityConfig) => void;
    onSave?: () => void;
    saving?: boolean;
    readOnly?: boolean;
}

export function ClaimCompanyConfigCard({ config, onChange, onSave, saving = false, readOnly }: ClaimCompanyConfigCardProps) {
    const { t } = useTranslation();

    return (
        <Card className="max-w-lg border bg-card">
            <CardHeader>
                <CardTitle>{t('Claim Module Visibility')}</CardTitle>
                <CardDescription>{t('Control which claim types employees can access.')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                {COMPANY_CONFIG_TOGGLES.map((item) => (
                    <div key={item.key} className="flex items-center justify-between gap-4">
                        <div className="space-y-0.5">
                            <Label htmlFor={item.key} className="text-base">
                                {t(item.label)}
                            </Label>
                            <p className="text-sm text-muted-foreground">{t(item.description)}</p>
                        </div>
                        <Switch
                            id={item.key}
                            checked={config[item.key]}
                            disabled={readOnly}
                            onCheckedChange={(checked) => onChange({ ...config, [item.key]: checked })}
                        />
                    </div>
                ))}
                {!readOnly && onSave && (
                    <Button onClick={onSave} disabled={saving} className="w-full sm:w-auto">
                        {saving && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {t('Save Configuration')}
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}
