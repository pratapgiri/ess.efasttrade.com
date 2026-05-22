import { PageTemplate } from '@/components/page-template';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Paperclip } from 'lucide-react';

export default function WorkFromHomeRequestShow() {
  const { t } = useTranslation();
  const { wfhRequest } = usePage().props as any;

  const statusColors: Record<string, string> = {
    pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
    approved: 'bg-green-50 text-green-700 ring-green-600/20',
    rejected: 'bg-red-50 text-red-700 ring-red-600/20',
  };

  const pageActions = [
    {
      label: t('Back'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: () => router.get(route('hr.wfh-applications.index')),
    },
  ];

  const details = [
    { label: t('Employee'), value: wfhRequest?.employee?.name || '-' },
    { label: t('Employee ID'), value: wfhRequest?.employee?.employee?.employee_id || '-' },
    { label: t('Department'), value: wfhRequest?.department || wfhRequest?.employee?.employee?.department?.name || '-' },
    { label: t('Designation'), value: wfhRequest?.designation || wfhRequest?.employee?.employee?.designation?.name || '-' },
    { label: t('WFH Start Date'), value: wfhRequest?.start_date ? (window.appSettings?.formatDateTimeSimple(wfhRequest.start_date, false) || wfhRequest.start_date) : '-' },
    { label: t('WFH End Date'), value: wfhRequest?.end_date ? (window.appSettings?.formatDateTimeSimple(wfhRequest.end_date, false) || wfhRequest.end_date) : '-' },
    { label: t('Applied On'), value: wfhRequest?.created_at ? (window.appSettings?.formatDateTimeSimple(wfhRequest.created_at, false) || wfhRequest.created_at) : '-' },
    { label: t('Created By'), value: wfhRequest?.creator?.name || '-' },
    { label: t('Approved/Rejected By'), value: wfhRequest?.approver?.name || '-' },
    { label: t('Decision Date'), value: wfhRequest?.approved_at ? (window.appSettings?.formatDateTimeSimple(wfhRequest.approved_at, false) || wfhRequest.approved_at) : '-' },
  ];

  return (
    <PageTemplate
      title={t('WFH Request Details')}
      url={`/hr/wfh-applications/${wfhRequest?.id}`}
      actions={pageActions}
      breadcrumbs={[
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('WFH Requests'), href: route('hr.wfh-applications.index') },
        { title: t('View Request') },
      ]}
    >
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>{t('Work From Home Request')}</CardTitle>
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColors[wfhRequest?.status] || ''}`}>
              {(wfhRequest?.status || '').charAt(0).toUpperCase() + (wfhRequest?.status || '').slice(1)}
            </span>
          </div>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            {details.map((item) => (
              <div key={item.label}>
                <div className="text-xs text-muted-foreground">{item.label}</div>
                <div className="text-sm font-medium">{item.value || '-'}</div>
              </div>
            ))}
          </div>

          <div>
            <div className="text-xs text-muted-foreground">{t('Reason')}</div>
            <div className="text-sm whitespace-pre-wrap">{wfhRequest?.reason || '-'}</div>
          </div>

          <div>
            <div className="text-xs text-muted-foreground">{t('Manager Comments')}</div>
            <div className="text-sm whitespace-pre-wrap">{wfhRequest?.manager_comments || '-'}</div>
          </div>

          <div>
            <div className="text-xs text-muted-foreground mb-2">{t('Attachment')}</div>
            {wfhRequest?.attachment ? (
              <Button variant="outline" size="sm" onClick={() => window.open(wfhRequest.attachment, '_blank')}>
                <Paperclip className="h-4 w-4 mr-2" />
                {t('View Attachment')}
              </Button>
            ) : (
              <div className="text-sm">-</div>
            )}
          </div>
        </CardContent>
      </Card>
    </PageTemplate>
  );
}
