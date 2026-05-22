// pages/hr/assets/dashboard.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { List, BarChart, PieChart, Calendar, AlertTriangle, DollarSign } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

export default function AssetDashboard() {
  const { t } = useTranslation();
  const { 
    assetCounts, 
    assetTypeData, 
    recentAssignments, 
    upcomingMaintenance, 
    expiringWarranties,
    assetValueSummary
  } = usePage().props as any;
  
  const handleViewAssets = () => {
    router.get(route('hr.assets.index'));
  };
  
  const handleViewDepreciationReport = () => {
    router.get(route('hr.assets.depreciation-report'));
  };
  
  // Define page actions
  const pageActions = [
    {
      label: t('Asset List'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewAssets
    },
    {
      label: t('Depreciation Report'),
      icon: <BarChart className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewDepreciationReport
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.assets.index') },
    { title: t('Asset Management'), href: route('hr.assets.index') },
    { title: t('Asset Dashboard') }
  ];
  
  // Status colors for badges
  const statusColors = {
    'available': 'bg-green-50 text-green-700 ring-green-600/20',
    'assigned': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'under_maintenance': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'disposed': 'bg-red-50 text-red-700 ring-red-600/20'
  };
  
  // Status labels
  const statusLabels = {
    'available': t('Available'),
    'assigned': t('Assigned'),
    'under_maintenance': t('Under Maintenance'),
    'disposed': t('Disposed')
  };
  
  return (
    <PageTemplate 
      title={t("Asset Dashboard")} 
      url="/hr/assets/dashboard"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Asset Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Total Assets')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{assetCounts.total}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Available')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-green-600 dark:text-green-400">{assetCounts.available}</p>
            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.available / assetCounts.total) * 100) : 0}% {t('of total')}
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Assigned')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">{assetCounts.assigned}</p>
            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.assigned / assetCounts.total) * 100) : 0}% {t('of total')}
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Under Maintenance')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">{assetCounts.under_maintenance}</p>
            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.under_maintenance / assetCounts.total) * 100) : 0}% {t('of total')}
            </p>
          </CardContent>
        </Card>
      </div>
      
      {/* Asset Value Summary */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Asset Value Summary')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex flex-col">
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">{t('Total Purchase Value')}</p>
              <p className="text-xl font-bold text-gray-900 dark:text-gray-100">{window.appSettings?.formatCurrency(assetValueSummary.total_purchase_value || 0)}</p>
            </div>
            <div className="flex flex-col">
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">{t('Total Current Value')}</p>
              <p className="text-xl font-bold text-gray-900 dark:text-gray-100">{window.appSettings?.formatCurrency(assetValueSummary.total_current_value || 0)}</p>
            </div>
            <div className="flex flex-col">
              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">{t('Total Depreciation')}</p>
              <p className="text-xl font-bold text-gray-900 dark:text-gray-100">{window.appSettings?.formatCurrency(assetValueSummary.total_depreciation || 0)}</p>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex justify-between mb-2">
              <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('Depreciation Progress')}</p>
              <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {Number(assetValueSummary.total_purchase_value || 0) > 0 
                  ? Math.round((Number(assetValueSummary.total_depreciation || 0) / Number(assetValueSummary.total_purchase_value || 0)) * 100) 
                  : 0}%
              </p>
            </div>
            <Progress 
              value={Number(assetValueSummary.total_purchase_value || 0) > 0 
                ? (Number(assetValueSummary.total_depreciation || 0) / Number(assetValueSummary.total_purchase_value || 0)) * 100 
                : 0} 
              className="h-2"
            />
          </div>
        </CardContent>
        <CardFooter>
          <Button variant="outline" onClick={handleViewDepreciationReport}>
            <BarChart className="h-4 w-4 mr-2" />
            {t('View Depreciation Report')}
          </Button>
        </CardFooter>
      </Card>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Asset Distribution by Type */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Asset Distribution by Type')}</CardTitle>
          </CardHeader>
          <CardContent>
            {assetTypeData.length > 0 ? (
              <div className="space-y-4">
                {assetTypeData.map((type: any, index: number) => (
                  <div key={index}>
                    <div className="flex justify-between mb-2">
                      <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{type.name}</p>
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{type.count}</p>
                    </div>
                    <Progress 
                      value={assetCounts.total > 0 ? (type.count / assetCounts.total) * 100 : 0} 
                      className="h-2"
                    />
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No asset data available')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Recent Assignments */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Recent Assignments')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentAssignments && recentAssignments.length > 0 ? (
              <div className="space-y-3">
                {recentAssignments.map((assignment: any) => (
                  <div key={assignment.id} className="flex items-start justify-between pb-3 border-b last:border-0">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">{assignment.asset?.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400">{t('Assigned to')}: {assignment.employee?.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        {window.appSettings?.formatDateTimeSimple(assignment.checkout_date, false) || format(new Date(assignment.checkout_date), 'MMM dd, yyyy')}
                      </p>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', assignment.asset_id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No recent assignments')}</div>
            )}
          </CardContent>
        </Card>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upcoming Maintenance */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Upcoming Maintenance')}</CardTitle>
          </CardHeader>
          <CardContent>
            {upcomingMaintenance && upcomingMaintenance.length > 0 ? (
              <div className="space-y-3">
                {upcomingMaintenance.map((maintenance: any) => (
                  <div key={maintenance.id} className="flex items-start justify-between pb-3 border-b last:border-0">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">{maintenance.asset?.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400">{maintenance.maintenance_type}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        {window.appSettings?.formatDateTimeSimple(maintenance.start_date, false) || format(new Date(maintenance.start_date), 'MMM dd, yyyy')}
                      </p>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', maintenance.asset_id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No upcoming maintenance')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Expiring Warranties */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Expiring Warranties')}</CardTitle>
          </CardHeader>
          <CardContent>
            {expiringWarranties && expiringWarranties.length > 0 ? (
              <div className="space-y-3">
                {expiringWarranties.map((asset: any) => (
                  <div key={asset.id} className="flex items-start justify-between pb-3 border-b last:border-0">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">{asset.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400">{asset.warranty_info}</p>
                      <p className="text-xs text-red-600 dark:text-red-400 mt-0.5">
                        {t('Expires')}: {window.appSettings?.formatDateTimeSimple(asset.warranty_expiry_date, false) || format(new Date(asset.warranty_expiry_date), 'MMM dd, yyyy')}
                      </p>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', asset.id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No expiring warranties')}</div>
            )}
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}