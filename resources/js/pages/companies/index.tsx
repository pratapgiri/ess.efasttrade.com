// pages/companies/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Card } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Filter, Search, Plus, Eye, Edit, Trash2, KeyRound, Lock, Unlock, LayoutGrid, List, Info, ArrowUpRight, CreditCard, History } from 'lucide-react';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';
import { DatePicker } from '@/components/ui/date-picker';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { UpgradePlanModal } from '@/components/UpgradePlanModal';
import { getImagePath } from '@/utils/helpers';

export default function Companies() {
  const { t } = useTranslation();
  const { auth, companies, plans, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const getInitials = useInitials();

  // State
  const [activeView, setActiveView] = useState(pageFilters.view || 'list');
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [startDate, setStartDate] = useState<Date | undefined>(pageFilters.start_date ? new Date(pageFilters.start_date) : undefined);
  const [endDate, setEndDate] = useState<Date | undefined>(pageFilters.end_date ? new Date(pageFilters.end_date) : undefined);
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [showFilters, setShowFilters] = useState(false);

  // Modal state
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isResetPasswordModalOpen, setIsResetPasswordModalOpen] = useState(false);
  const [isUpgradePlanModalOpen, setIsUpgradePlanModalOpen] = useState(false);

  const [currentCompany, setCurrentCompany] = useState<any>(null);
  const [availablePlans, setAvailablePlans] = useState<any[]>([]);


  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedStatus !== 'all' || searchTerm !== '' || startDate !== undefined || endDate !== undefined;
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedStatus !== 'all' ? 1 : 0) +
      (searchTerm ? 1 : 0) +
      (startDate ? 1 : 0) +
      (endDate ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    if (selectedStatus !== 'all') {
      params.status = selectedStatus;
    }

    if (startDate) {
      params.start_date = startDate.toISOString().split('T')[0];
    }

    if (endDate) {
      params.end_date = endDate.toISOString().split('T')[0];
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    params.view = activeView;
    router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleStatusFilter = (value: string) => {
    setSelectedStatus(value);

    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    if (value !== 'all') {
      params.status = value;
    }

    if (startDate) {
      params.start_date = startDate.toISOString().split('T')[0];
    }

    if (endDate) {
      params.end_date = endDate.toISOString().split('T')[0];
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    params.view = activeView;
    router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    const params: any = {
      sort_field: field,
      sort_direction: direction,
      page: 1
    };

    // Add search and filters
    if (searchTerm) {
      params.search = searchTerm;
    }

    if (selectedStatus !== 'all') {
      params.status = selectedStatus;
    }

    if (startDate) {
      params.start_date = startDate.toISOString().split('T')[0];
    }

    if (endDate) {
      params.end_date = endDate.toISOString().split('T')[0];
    }

    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    params.view = activeView;
    router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, company: any) => {
    setCurrentCompany(company);

    switch (action) {
      case 'login-as':
        router.get(route("impersonate.start", company.id));
        break;
      case 'company-info':
        setFormMode('view');
        setIsFormModalOpen(true);
        break;
      case 'upgrade-plan':
        handleUpgradePlan(company);
        break;

      case 'reset-password':
        setIsResetPasswordModalOpen(true);
        break;
      case 'toggle-status':
        handleToggleStatus(company);
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      default:
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentCompany(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Creating company...'));
      }

      router.post(route('companies.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
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
            toast.error(t('Failed to create company: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Updating company...'));
      }

      router.put(route('companies.update', currentCompany.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
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
            toast.error(t('Failed to update company: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Deleting company...'));
    }

    router.delete(route("companies.destroy", currentCompany.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
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
          toast.error(t('Failed to delete company: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetPasswordConfirm = (data: { password: string }) => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Resetting password...'));
    }

    router.put(route('companies.reset-password', currentCompany.id), data, {
      onSuccess: (page) => {
        setIsResetPasswordModalOpen(false);
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
          toast.error(t('Failed to reset password: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleToggleStatus = (company: any) => {
    const newStatus = company.status === 'active' ? 'inactive' : 'active';
    if (!globalSettings?.is_demo) {
      toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} company...`);
    }

    router.put(route('companies.toggle-status', company.id), {}, {
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
          toast.error(t('Failed to update company status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handlePageChange = (url: string) => {
    const pageNum = new URL(url).searchParams.get('page') || '1';
    const params: any = { page: pageNum, view: activeView };
    if (searchTerm) params.search = searchTerm;
    if (selectedStatus !== 'all') params.status = selectedStatus;
    if (startDate) params.start_date = startDate.toISOString().split('T')[0];
    if (endDate) params.end_date = endDate.toISOString().split('T')[0];
    if (pageFilters.per_page) params.per_page = pageFilters.per_page;
    if (pageFilters.sort_field) params.sort_field = pageFilters.sort_field;
    if (pageFilters.sort_direction) params.sort_direction = pageFilters.sort_direction;
    router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
  };

  const handleResetFilters = () => {
    setSelectedStatus('all');
    setSearchTerm('');
    setStartDate(undefined);
    setEndDate(undefined);
    setShowFilters(false);

    router.get(route('companies.index'), {
      page: 1,
      per_page: pageFilters.per_page,
      view: activeView
    }, { preserveState: true, preserveScroll: true });
  };

  const handleUpgradePlan = (company: any) => {
    setCurrentCompany(company);

    // Fetch available plans
    if (!globalSettings?.is_demo) {
      toast.loading(t('Loading plans...'));
    }
    fetch(route('companies.plans', company.id))
      .then(res => res.json())
      .then(data => {
        setAvailablePlans(data.plans);
        setIsUpgradePlanModalOpen(true);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
      })
      .catch(err => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        toast.error(t('Failed to load plans'));
      });
  };

  const handleUpgradePlanConfirm = (planId: number, duration: string) => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Upgrading plan...'));
    }

    // Use Inertia router to handle the request
    router.put(route('companies.upgrade-plan', currentCompany.id), {
      plan_id: planId,
      duration: duration

    }, {
      onSuccess: () => {
        setIsUpgradePlanModalOpen(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        toast.success(t('Plan upgraded successfully'));
        router.reload();
      },
      onError: () => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        toast.error(t('Failed to upgrade plan'));
      }
    });
  };





  // Define page actions
  const pageActions = [
    ...(permissions.includes('manage-login-history') ? [{
      icon: <History className="h-4 w-4 mx-auto" />,
      variant: 'outline',
      onClick: () => router.get(route('login-history.index')),
      tooltip: t('Login History')
    }] : []),
    {
      label: t('Add Company'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew(),
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Companies') }
  ];

  // Define table columns for list view
  const columns = [
    {
      key: 'name',
      label: t('Name'),
      sortable: true,
      render: (value: any, row: any) => {
        const avatarUrl = row.avatar ? getImagePath(row.avatar) : getImagePath('avatars/avatar.png');
        return (
          <div className="flex items-center gap-3">
            <img
              src={avatarUrl}
              alt={row.name}
              className="h-10 w-10 rounded-full object-cover"
              onError={(e) => {
                const target = e.target as HTMLImageElement;
                target.src = getImagePath('avatars/avatar.png');
              }}
            />
            <div>
              <div className="font-medium">{row.name}</div>
              <div className="text-sm text-muted-foreground">{row.email}</div>
            </div>
          </div>
        );
      }
    },
    {
      key: 'plan_name',
      label: t('Plan'),
      render: (value: string) => (
        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 capitalize">
          {value}
        </span>
      )
    },
    {
      key: 'created_at',
      label: t('Created At'),
      sortable: true,
      render: (value: string) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  return (
    <PageTemplate
      title={t("Companies")}
      url="/companies"
      actions={pageActions}
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
              onChange: handleStatusFilter,
              options: [
                { value: 'all', label: t('All Status') },
                { value: 'active', label: t('Active') },
                { value: 'inactive', label: t('Inactive') }
              ]
            },
            {
              name: 'start_date',
              label: t('Start Date'),
              type: 'date',
              value: startDate,
              onChange: (date) => setStartDate(date)
            },
            {
              name: 'end_date',
              label: t('End Date'),
              type: 'date',
              value: endDate,
              onChange: (date) => setEndDate(date)
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
            const params: any = { page: 1, per_page: parseInt(value) };

            if (searchTerm) {
              params.search = searchTerm;
            }

            if (selectedStatus !== 'all') {
              params.status = selectedStatus;
            }

            if (startDate) {
              params.start_date = startDate.toISOString().split('T')[0];
            }

            if (endDate) {
              params.end_date = endDate.toISOString().split('T')[0];
            }

            params.view = activeView;
            router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
          }}
          showViewToggle={true}
          activeView={activeView}
          onViewChange={(view) => {
            setActiveView(view);
            const params: any = { page: 1, view };
            if (searchTerm) params.search = searchTerm;
            if (selectedStatus !== 'all') params.status = selectedStatus;
            if (startDate) params.start_date = startDate.toISOString().split('T')[0];
            if (endDate) params.end_date = endDate.toISOString().split('T')[0];
            if (pageFilters.per_page) params.per_page = pageFilters.per_page;
            router.get(route('companies.index'), params, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      {activeView === 'list' ? (
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                  {columns.map((column) => (
                    <th
                      key={column.key}
                      className="px-4 py-3 text-left font-medium text-gray-500"
                      onClick={() => column.sortable && handleSort(column.key)}
                    >
                      <div className="flex items-center">
                        {column.label}
                        {column.sortable && (
                          <span className="ml-1">
                            {pageFilters.sort_field === column.key ? (
                              pageFilters.sort_direction === 'asc' ? '↑' : '↓'
                            ) : ''}
                          </span>
                        )}
                      </div>
                    </th>
                  ))}
                  <th className="px-4 py-3 text-right font-medium text-gray-500">
                    {t("Actions")}
                  </th>
                </tr>
              </thead>
              <tbody>
                {companies?.data?.map((company: any) => (
                  <tr key={company.id} className="border-b hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
                    {columns.map((column) => (
                      <td key={`${company.id}-${column.key}`} className="px-4 py-3">
                        {column.render ? column.render(company[column.key], company) : company[column.key]}
                      </td>
                    ))}
                    <td className="px-4 py-3 text-right">
                      <div className="flex justify-end gap-1">
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('login-as', company)}
                              className="text-blue-500 hover:text-blue-700"
                            >
                              <ArrowUpRight className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Login as Company")}</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('company-info', company)}
                              className="text-blue-500 hover:text-blue-700"
                            >
                              <Info className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Company Info")}</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('upgrade-plan', company)}
                              className="text-amber-500 hover:text-amber-700"
                            >
                              <CreditCard className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Upgrade Plan")}</TooltipContent>
                        </Tooltip>



                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('reset-password', company)}
                              className="text-blue-500 hover:text-blue-700"
                            >
                              <KeyRound className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Reset Password")}</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('toggle-status', company)}
                              className="text-amber-500 hover:text-amber-700"
                            >
                              {company.status === 'active' ? <Lock className="h-4 w-4" /> : <Unlock className="h-4 w-4" />}
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{company.status === 'active' ? t("Disable Login") : t("Enable Login")}</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleAction('edit', company)}
                              className="text-amber-500 hover:text-amber-700"
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Edit")}</TooltipContent>
                        </Tooltip>

                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-red-500 hover:text-red-700"
                              onClick={() => handleAction('delete', company)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>{t("Delete")}</TooltipContent>
                        </Tooltip>
                      </div>
                    </td>
                  </tr>
                ))}

                {(!companies?.data || companies.data.length === 0) && (
                  <tr>
                    <td colSpan={columns.length + 1} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                      {t("No companies found")}
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination section */}
          <Pagination
            from={companies?.from || 0}
            to={companies?.to || 0}
            total={companies?.total || 0}
            links={companies?.links}
            entityName={t("companies")}
            onPageChange={handlePageChange}
          />
        </div>
      ) : (
        <div>
          {/* Grid View */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {companies?.data?.map((company: any) => (
              <Card key={company.id} className="group relative overflow-hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300">
                {/* Status Badge */}
                <div className="absolute top-4 right-4 z-10">
                  <div className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${company.status === 'active'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                      : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    }`}>
                    <div className={`w-1.5 h-1.5 rounded-full mr-1.5 ${company.status === 'active' ? 'bg-green-500' : 'bg-red-500'
                      }`}></div>
                    {company.status === 'active' ? t('Active') : t('Inactive')}
                  </div>
                </div>

                {/* Card Content */}
                <div className="p-6">
                  {/* Company Header */}
                  <div className="flex items-start space-x-4 mb-6">
                    <div className="relative">
                      <img
                        src={company.avatar}
                        alt={company.name}
                        className="h-14 w-14 rounded-full object-cover shadow-sm"
                        onError={(e) => {
                          const target = e.target as HTMLImageElement;
                          target.src = getImagePath('avatars/avatar.png');
                        }}
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1 truncate">
                        {company.name}
                      </h3>
                      <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {company.email}
                      </p>
                    </div>
                  </div>

                  {/* Plan Information */}
                  <div className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 mb-6">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center">
                        <CreditCard className="h-4 w-4 text-primary mr-2" />
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {company.plan_name}
                        </span>
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleAction('upgrade-plan', company)}
                        className="h-6 px-2 text-xs text-primary hover:text-primary hover:bg-primary/10"
                      >
                        {t("Upgrade")}
                      </Button>
                    </div>
                    {company.plan_expiry_date && (
                      <div className="text-xs text-gray-500 dark:text-gray-400">
                        {t("Expires")}: {window.appSettings?.formatDateTimeSimple(company.plan_expiry_date, false) || new Date(company.plan_expiry_date).toLocaleDateString()}
                      </div>
                    )}
                  </div>

                  {/* Quick Actions */}
                  <div className="flex items-center justify-between">
                    <div className="flex space-x-1">
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleAction('login-as', company)}
                            className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                          >
                            <ArrowUpRight className="h-4 w-4" />
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>{t("Login as Company")}</TooltipContent>
                      </Tooltip>

                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleAction('company-info', company)}
                            className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                          >
                            <Info className="h-4 w-4" />
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>{t("Company Info")}</TooltipContent>
                      </Tooltip>

                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleAction('edit', company)}
                            className="h-8 w-8 p-0 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>{t("Edit")}</TooltipContent>
                      </Tooltip>
                    </div>

                    {/* More Actions Dropdown */}
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="12" cy="5" r="1"></circle>
                            <circle cx="12" cy="19" r="1"></circle>
                          </svg>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                        <DropdownMenuItem onClick={() => handleAction('reset-password', company)}>
                          <KeyRound className="h-4 w-4 mr-2" />
                          <span>{t("Reset Password")}</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem onClick={() => handleAction('toggle-status', company)}>
                          {company.status === 'active' ?
                            <Lock className="h-4 w-4 mr-2" /> :
                            <Unlock className="h-4 w-4 mr-2" />
                          }
                          <span>{company.status === 'active' ? t("Disable Login") : t("Enable Login")}</span>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => handleAction('delete', company)} className="text-red-600 focus:text-red-600">
                          <Trash2 className="h-4 w-4 mr-2" />
                          <span>{t("Delete")}</span>
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>

                {/* Hover Effect Overlay - REMOVED */}
              </Card>
            ))}

            {(!companies?.data || companies.data.length === 0) && (
              <div className="col-span-full">
                <div className="text-center py-12">
                  <div className="mx-auto h-24 w-24 text-gray-300 dark:text-gray-600 mb-4">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" className="w-full h-full">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">{t("No companies found")}</h3>
                  <p className="text-gray-500 dark:text-gray-400 mb-6">{t("Get started by creating your first company")}</p>
                  <Button onClick={handleAddNew} className="inline-flex items-center">
                    <Plus className="h-4 w-4 mr-2" />
                    {t("Add Company")}
                  </Button>
                </div>
              </div>
            )}
          </div>

          {/* Pagination for grid view */}
          <div className="mt-8">
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <Pagination
                from={companies?.from || 0}
                to={companies?.to || 0}
                total={companies?.total || 0}
                links={companies?.links}
                entityName={t("companies")}
                onPageChange={handlePageChange}
              />
            </div>
          </div>
        </div>
      )}

      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={(data) => {
          // If login_enabled is false, remove password field
          if (data.login_enabled === false) {
            delete data.password;
          }
          // Set status based on login_enabled
          data.status = data.login_enabled ? 'active' : 'inactive';

          // Remove login_enabled field as it's not needed in the backend
          delete data.login_enabled;
          handleFormSubmit(data);
        }}
        formConfig={{
          fields: [
            { name: 'name', label: t('Company Name'), type: 'text', required: true },
            { name: 'email', label: t('Email'), type: 'email', required: true },
            {
              name: 'login_enabled',
              label: t('Enable Login'),
              placeholder: '', // Empty placeholder to prevent duplicate label
              type: 'switch',
              defaultValue: true,
              conditional: (mode) => mode === 'create'
            },
            {
              name: 'password',
              label: t('Password'),
              type: 'password',
              required: (mode) => mode === 'create',
              conditional: (mode, data) => {
                return mode === 'create' && data?.login_enabled === true;
              }
            }
          ],
          modalSize: 'lg'
        }}
        initialData={{
          ...currentCompany,
          login_enabled: currentCompany?.status === 'active'
        }}
        title={
          formMode === 'create'
            ? t('Add New Company')
            : formMode === 'edit'
              ? t('Edit Company')
              : t('View Company')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentCompany?.name || ''}
        entityName="company"
      />

      {/* Reset Password Modal */}
      <CrudFormModal
        isOpen={isResetPasswordModalOpen}
        onClose={() => setIsResetPasswordModalOpen(false)}
        onSubmit={handleResetPasswordConfirm}
        formConfig={{
          fields: [
            { name: 'password', label: t('New Password'), type: 'password', required: true }
          ],
          modalSize: 'sm'
        }}
        initialData={{}}
        title={`Reset Password for ${currentCompany?.name || 'Company'}`}
        mode="edit"
      />

      {/* Upgrade Plan Modal */}
      <UpgradePlanModal
        isOpen={isUpgradePlanModalOpen}
        onClose={() => setIsUpgradePlanModalOpen(false)}
        onConfirm={handleUpgradePlanConfirm}
        plans={availablePlans}
        currentPlanId={currentCompany?.plan_id}
        companyName={currentCompany?.name || ''}
      />


    </PageTemplate>
  );
}