import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslation } from 'react-i18next';
import { getVisibleClaimTypes, type ClaimTypeKey, type ClaimVisibilityConfig } from '@/config/claims';
import { DEFAULT_VISIBILITY } from '@/config/claims/claim-types';

interface ClaimTabsProps {
    value: ClaimTypeKey;
    onChange: (value: ClaimTypeKey) => void;
    visibility?: ClaimVisibilityConfig;
    className?: string;
}

export function ClaimTabs({ value, onChange, visibility = DEFAULT_VISIBILITY, className }: ClaimTabsProps) {
    const { t } = useTranslation();
    const visibleTabs = getVisibleClaimTypes(visibility);
    const activeValue = visibleTabs.some((tab) => tab.key === value)
        ? value
        : (visibleTabs[0]?.key ?? value);

    if (visibleTabs.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">{t('No claim types are enabled for your company.')}</p>
        );
    }

    return (
        <Tabs value={activeValue} onValueChange={(v) => onChange(v as ClaimTypeKey)} className={className}>
            <TabsList className="flex h-auto w-full flex-wrap gap-1 bg-muted/50 p-1 sm:w-auto">
                {visibleTabs.map((tab) => {
                    const Icon = tab.icon;
                    return (
                        <TabsTrigger
                            key={tab.key}
                            value={tab.key}
                            className="gap-1.5 data-[state=active]:bg-background data-[state=active]:shadow-sm"
                        >
                            <Icon className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">{t(tab.label)}</span>
                            <span className="sm:hidden">{t(tab.label).split(' ')[0]}</span>
                        </TabsTrigger>
                    );
                })}
            </TabsList>
        </Tabs>
    );
}

export { getDefaultClaimType } from '@/config/claims';
