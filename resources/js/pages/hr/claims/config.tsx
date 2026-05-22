// pages/hr/claims/config.tsx — Company Claim Configuration
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { toast } from '@/components/custom-toast';
import { ClaimCompanyConfigCard } from '@/components/claims';
import { DEFAULT_VISIBILITY, mapInertiaConfigToVisibility } from '@/config/claims';
import type { ClaimVisibilityConfig } from '@/config/claims';

export default function ClaimConfigPage() {
    const { t } = useTranslation();
    const inertiaConfig = (usePage().props as { config?: Record<string, boolean> }).config;

    const [config, setConfig] = useState<ClaimVisibilityConfig>(
        mapInertiaConfigToVisibility(inertiaConfig)
    );
    const [saving, setSaving] = useState(false);

    return (
        <PageTemplate
            title={t('Claim Configuration')}
            description={t('Enable or disable claim types for your organization')}
            url="/hr/claim-config"
            breadcrumbs={[
                { title: t('Dashboard'), href: route('dashboard') },
                { title: t('Settings') },
                { title: t('Claim Configuration') },
            ]}
        >
            <ClaimCompanyConfigCard
                config={config}
                onChange={setConfig}
                saving={saving}
                onSave={() => {
                    toast.loading(t('Saving configuration...'));
                    setSaving(true);
                    router.put(
                        route('hr.claim-config.update'),
                        {
                            expenses_available: config.EXPENSES_AVAILABLE,
                            local_conveyance_available: config.LOCAL_CONVEYANCE_AVAILABLE,
                            intercity_conveyance_available: config.INTERCITY_CONVEYANCE_AVAILABLE,
                        },
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setSaving(false);
                                toast.dismiss();
                            },
                            onSuccess: (page) => {
                                const flash = page.props.flash as { success?: string; error?: string } | undefined;
                                if (flash?.success) toast.success(t(flash.success));
                                else if (flash?.error) toast.error(t(flash.error));
                            },
                            onError: () => toast.error(t('Failed to save configuration')),
                        }
                    );
                }}
            />
        </PageTemplate>
    );
}
