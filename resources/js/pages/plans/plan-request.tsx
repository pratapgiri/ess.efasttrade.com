// pages/plans/plan-request.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { CrudTable } from '@/components/CrudTable';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { CheckCircle, XCircle } from 'lucide-react';
import { getImagePath } from '@/utils/helpers';

export default function PlanRequestsPage() {
  const { t } = useTranslation();
  const { planRequests, filters: pageFilters = {}, auth, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedStatus !== '_empty_' || searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedStatus !== '_empty_' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('plan-requests.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('plan-requests.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    if (action === 'approve') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Approving plan request...'));
      }
      
      router.post(route('plan-requests.approve', item.id), {}, {
        onSuccess: (page) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to approve plan request: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (action === 'reject') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Rejecting plan request...'));
      }
      
      router.post(route('plan-requests.reject', item.id), {}, {
        onSuccess: (page) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to reject plan request: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedStatus('_empty_');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('plan-requests.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Plans'), href: route('plans.index') },
    { title: t('Plan Requests') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'user.name', 
      label: t('Company'), 
      render: (_, row) => {
        const avatarUrl = row.user?.avatar ? getImagePath(row.user.avatar) : getImagePath('avatars/avatar.png');
        return (
          <div className="flex items-center gap-3">
            <img 
              src={avatarUrl} 
              alt={row.user?.name || 'User'}
              className="h-10 w-10 rounded-full object-cover"
              onError={(e) => {
                const target = e.target as HTMLImageElement;
                target.src = getImagePath('avatars/avatar.png');
              }}
            />
            <div>
              <div className="font-medium">{row.user?.name || '-'}</div>
              <div className="text-xs text-gray-500">{row.user?.email || ''}</div>
            </div>
          </div>
        );
      }
    },
    { 
      key: 'plan.name', 
      label: t('Plan'),
      render: (_, row) => {
        const planName = row.plan?.name;
        if (!planName) return '-';
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 capitalize">
            {planName}
          </span>
        );
      }
    },
    { 
      key: 'plan.duration', 
      label: t('Duration'),
      render: (_, row) => {
        const duration = row.plan?.duration;
        if (!duration) return '-';
        return duration === 'monthly' ? t('Monthly') : t('Yearly');
      }
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => {
        const statusColors = {
          pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
          approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
          rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
        };
        return (
          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${statusColors[value] || 'bg-gray-100 text-gray-800'}`}>
            {t(value)}
          </span>
        );
      }
    },
    { 
      key: 'created_at', 
      label: t('Request Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  // Define table actions - only visible to super admin
  const isSuperAdmin = auth?.user?.type === 'superadmin';
  const actions = isSuperAdmin ? [
    { 
      label: t('Approve'), 
      icon: 'Check', 
      action: 'approve', 
      className: 'text-green-500',
      requiredPermission: 'approve-plan-requests',
      condition: (row) => row.status === 'pending'
    },
    { 
      label: t('Reject'), 
      icon: 'X', 
      action: 'reject', 
      className: 'text-red-500',
      requiredPermission: 'reject-plan-requests',
      condition: (row) => row.status === 'pending'
    }
  ] : [];

  // Prepare status options for filter
  const statusOptions = [
    { value: '_empty_', label: t('All Status') },
    { value: 'pending', label: t('Pending') },
    { value: 'approved', label: t('Approved') },
    { value: 'rejected', label: t('Rejected') }
  ];

  return (
    <PageTemplate 
      title={t('Plan Requests')} 
      url="/plan-requests"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions
            }
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('plan-requests.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: selectedStatus !== '_empty_' ? selectedStatus : undefined,
              date_from: dateFrom || undefined,
              date_to: dateTo || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={planRequests?.data || []}
          from={planRequests?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
        />

        {/* Pagination section */}
        <Pagination
          from={planRequests?.from || 0}
          to={planRequests?.to || 0}
          total={planRequests?.total || 0}
          links={planRequests?.links}
          entityName={t("plan requests")}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}