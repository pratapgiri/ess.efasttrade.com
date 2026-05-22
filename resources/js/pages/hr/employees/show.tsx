// pages/hr/employees/show.tsx
import { useState, useEffect } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { hasPermission } from '@/utils/authorization';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';
import { Edit, Trash2, Download, FileText, Calendar, Phone, Mail, MapPin, Building, Briefcase, CreditCard, User, Lock, Unlock, ArrowLeft, Check, X, ChevronDown } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export default function EmployeeShow() {
  const { t } = useTranslation();
  const { auth, employee, flash } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const getInitials = useInitials();

  // State
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('basic_info');

  useEffect(() => {
    if (flash?.success) {
      toast.success(t(flash.success));
    }
    if (flash?.error) {
      toast.error(t(flash.error));
    }
  }, [flash, t]);

  const handleEdit = () => {
    router.get(route('hr.employees.edit', employee.id));
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee...'));

    router.delete(route('hr.employees.destroy', employee.id), {
      onSuccess: (page) => {
        toast.dismiss();
        // if (page.props.flash.success) {
        //   toast.success(t(page.props.flash.success));
        // } else if (page.props.flash.error) {
        //   toast.error(t(page.props.flash.error));
        // }
        router.get(route('hr.employees.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete employee: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleToggleStatus = () => {
    const isCurrentlyActive = employee.status === 'active' && employee.employee?.employee_status === 'active';
    const newStatus = isCurrentlyActive ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} employee...`);

    router.put(route('hr.employees.toggle-status', employee.id), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update employee status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDeleteDocument = (documentId: number) => {
    toast.loading(t('Deleting document...'));

    router.delete(route('hr.employees.documents.destroy', [employee.id, documentId]), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete document: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleDocumentVerification = (documentId: number, status: 'verified' | 'rejected') => {
    const action = status === 'verified' ? 'approve' : 'reject';
    toast.loading(t(`${status === 'verified' ? 'Approving' : 'Rejecting'} document...`));

    router.put(route(`hr.employees.documents.${action}`, [employee.id, documentId]), {}, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash?.success) {
          toast.success(t(page.props.flash.success));
        } else {
          toast.success(t(`Document ${status === 'verified' ? 'approved' : 'rejected'} successfully`));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        const errorMessage = errors?.message || Object.values(errors)[0] || `Failed to ${action} document`;
        toast.error(t(errorMessage));
      }
    });
  };

  // Document download handlers
  const handleDocumentDownload = (documentType: string, format: string) => {
    if (documentType === 'joining_letter') {
      const url = route('hr.employees.download-joining-letter', [employee.id, format]);
      window.open(url, '_blank');
    } else if (documentType === 'experience_certificate') {
      const url = route('hr.employees.download-experience-certificate', [employee.id, format]);
      window.open(url, '_blank');
    } else if (documentType === 'noc') {
      const url = route('hr.employees.download-noc-certificate', [employee.id, format]);
      window.open(url, '_blank');
    }
  };

  // Define page actions
  const pageActions = [
    {
      label: t('Back'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('hr.employees.index'))
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.employees.index') },
    { title: t('Employees'), href: route('hr.employees.index') },
    { title: employee?.name || t('Employee Details') }
  ];

  return (
    <PageTemplate
      title={employee?.name || t("Employee Details")}
      url={`/hr/employees/${employee?.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="grid grid-cols-1 xl:grid-cols-4 gap-4 lg:gap-6">
        {/* Employee Profile Card */}
        <Card className="xl:col-span-1">
          <CardContent className="p-4 sm:p-6">
            <div className="flex flex-col items-center">
              <div className="h-24 w-24 sm:h-32 sm:w-32 rounded-full bg-primary text-white flex items-center justify-center text-2xl sm:text-3xl font-bold mb-4 overflow-hidden">
                {employee.avatar ? (
                  <img src={employee.avatar} alt={employee.name} className="h-full w-full object-cover" />
                ) : (
                  getInitials(employee.name)
                )}
              </div>
              <h2 className="text-lg sm:text-xl font-bold mb-1 text-center text-gray-900 dark:text-gray-100">{employee.name}</h2>
              <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-2 text-center">{employee.employee?.designation?.name || '-'}</p>
              <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mb-4 ${employee.employee?.employee_status === 'active'
                ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                : employee.employee?.employee_status === 'inactive'
                  ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
                  : employee.employee?.employee_status === 'probation'
                    ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20'
                    : employee.employee?.employee_status === 'terminated'
                      ? 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                      : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                }`}>
                {employee.employee?.employee_status === 'active' && t('Active')}
                {employee.employee?.employee_status === 'inactive' && t('Inactive')}
                {employee.employee?.employee_status === 'probation' && t('Probation')}
                {employee.employee?.employee_status === 'terminated' && t('Terminated')}
                {!employee.employee?.employee_status && '-'}
              </div>

              <div className="w-full space-y-3">
                <div className="flex items-center">
                  <User className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                  <span className="text-sm text-gray-900 dark:text-gray-100">{t('Employee ID')}: {employee.employee?.employee_id}</span>
                </div>
                <div className="flex items-center">
                  <Mail className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                  <span className="text-sm text-gray-900 dark:text-gray-100">{employee.email}</span>
                </div>
                {employee.employee?.phone && (
                  <div className="flex items-center">
                    <Phone className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{employee.employee.phone}</span>
                  </div>
                )}
                {employee.employee?.date_of_birth && (
                  <div className="flex items-center">
                    <Calendar className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{t('DOB')}: {window.appSettings?.formatDateTimeSimple(employee.employee.date_of_birth, false) || new Date(employee.employee.date_of_birth).toLocaleDateString()}</span>
                  </div>
                )}
                {employee.employee?.date_of_joining && (
                  <div className="flex items-center">
                    <Calendar className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{t('Joined')}: {window.appSettings?.formatDateTimeSimple(employee.employee.date_of_joining, false) || new Date(employee.employee.date_of_joining).toLocaleDateString()}</span>
                  </div>
                )}
                {employee.employee?.department?.name && (
                  <div className="flex items-center">
                    <Building className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{employee.employee.department.name}</span>
                  </div>
                )}
                {employee.employee?.branch?.name && (
                  <div className="flex items-center">
                    <Building className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{employee.employee.branch.name}</span>
                  </div>
                )}
                {employee.employee?.employment_type && (
                  <div className="flex items-center">
                    <Briefcase className="h-4 w-4 mr-2 text-gray-500 dark:text-gray-400" />
                    <span className="text-sm text-gray-900 dark:text-gray-100">{employee.employee.employment_type}</span>
                  </div>
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Employee Details Tabs */}
        <div className="xl:col-span-3">
          <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
            <TabsList className="grid grid-cols-6 mb-4">
              <TabsTrigger value="basic_info">{t('Basic Info')}</TabsTrigger>
              <TabsTrigger value="employment">{t('Employment')}</TabsTrigger>
              <TabsTrigger value="contact">{t('Contact')}</TabsTrigger>
              <TabsTrigger value="banking">{t('Banking')}</TabsTrigger>
              {(hasPermission(permissions, 'download-joining-letter') || hasPermission(permissions, 'download-experience-certificate') || hasPermission(permissions, 'download-noc-certificate')) && (
                <TabsTrigger value="certifications">{t('Certifications')}</TabsTrigger>
              )}
              <TabsTrigger value="documents">{t('Documents')}</TabsTrigger>
            </TabsList>

            {/* Basic Info Tab */}
            <TabsContent value="basic_info">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Basic Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Full Name')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.name}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Employee ID')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.employee_id}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Email')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.email}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Phone Number')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.phone || '-'}</p>
                    </div>
                     <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Employee Code')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.biometric_emp_id || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Date of Birth')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.date_of_birth ? (window.appSettings?.formatDateTimeSimple(employee.employee.date_of_birth, false) || new Date(employee.employee.date_of_birth).toLocaleDateString()) : '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Gender')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.gender ? t(employee.employee.gender.charAt(0).toUpperCase() + employee.employee.gender.slice(1)) : '-'}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Employment Tab */}
            <TabsContent value="employment">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Employment Details')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Branch')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.branch?.name || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Department')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.department?.name || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Designation')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.designation?.name || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Date of Joining')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.date_of_joining ? (window.appSettings?.formatDateTimeSimple(employee.employee.date_of_joining, false) || new Date(employee.employee.date_of_joining).toLocaleDateString()) : '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Employment Type')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.employment_type || '-'}</p>
                    </div>

                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Shift')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.shift ? `${employee.employee.shift.name} (${employee.employee.shift.start_time} - ${employee.employee.shift.end_time})` : '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Attendance Policy')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.attendance_policy?.name || '-'}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Contact Tab */}
            <TabsContent value="contact">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Contact Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Address Line 1')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.address_line_1 || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Address Line 2')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.address_line_2 || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('City')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.city || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('State/Province')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.state || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Country')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.country || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Postal/Zip Code')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.postal_code || '-'}</p>
                    </div>
                  </div>

                  <div className="mt-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{t('Emergency Contact')}</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Name')}</h3>
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.emergency_contact_name || '-'}</p>
                      </div>
                      <div>
                        <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Relationship')}</h3>
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.emergency_contact_relationship || '-'}</p>
                      </div>
                      <div>
                        <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Phone Number')}</h3>
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.emergency_contact_number || '-'}</p>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Banking Tab */}
            <TabsContent value="banking">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Banking Information')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Bank Name')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.bank_name || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Account Holder Name')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.account_holder_name || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Account Number')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.account_number || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Bank Identifier Code (BIC/SWIFT)')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.bank_identifier_code || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Bank Branch')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.bank_branch || '-'}</p>
                    </div>
                    <div>
                      <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Tax Payer ID')}</h3>
                      <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee?.tax_payer_id || '-'}</p>
                    </div>
                    {employee.employee?.base_salary && (
                      <div>
                        <h3 className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Base Salary')}</h3>
                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">{employee.employee.base_salary}</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Certifications Tab */}
            <TabsContent value="certifications">
              {(hasPermission(permissions, 'download-joining-letter') || hasPermission(permissions, 'download-experience-certificate') || hasPermission(permissions, 'download-noc-certificate')) && (
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Employee Certifications')}</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      {/* Joining Letter */}
                      {hasPermission(permissions, 'download-joining-letter') && (
                        <Card className="border">
                          <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center">
                                <FileText className="h-8 w-8 mr-3 text-muted-foreground" />
                                <div>
                                  <h4 className="font-medium text-gray-900 dark:text-gray-100">{t('Joining Letter')}</h4>
                                  <p className="text-sm text-gray-600 dark:text-gray-400">{t('Official joining letter document')}</p>
                                </div>
                              </div>
                            </div>
                            <div className="mt-4">
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="outline" className="w-full">
                                    <Download className="mr-2 h-4 w-4" />
                                    {t('Download')}
                                    <ChevronDown className="ml-2 h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-full">
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('joining_letter', 'pdf')}>
                                    {t('PDF')}
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('joining_letter', 'doc')}>
                                    {t('DOC')}
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          </CardContent>
                        </Card>
                      )}

                      {/* Experience Certificate */}
                      {hasPermission(permissions, 'download-experience-certificate') && (
                        <Card className="border">
                          <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center">
                                <FileText className="h-8 w-8 mr-3 text-muted-foreground" />
                                <div>
                                  <h4 className="font-medium text-gray-900 dark:text-gray-100">{t('Experience Certificate')}</h4>
                                  <p className="text-sm text-gray-600 dark:text-gray-400">{t('Work experience certificate')}</p>
                                </div>
                              </div>
                            </div>
                            <div className="mt-4">
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="outline" className="w-full">
                                    <Download className="mr-2 h-4 w-4" />
                                    {t('Download')}
                                    <ChevronDown className="ml-2 h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-full">
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('experience_certificate', 'pdf')}>
                                    {t('PDF')}
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('experience_certificate', 'doc')}>
                                    {t('DOC')}
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          </CardContent>
                        </Card>
                      )}

                      {/* NOC */}
                      {hasPermission(permissions, 'download-noc-certificate') && (
                        <Card className="border">
                          <CardContent className="p-4">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center">
                                <FileText className="h-8 w-8 mr-3 text-muted-foreground" />
                                <div>
                                  <h4 className="font-medium text-gray-900 dark:text-gray-100">{t('NOC')}</h4>
                                  <p className="text-sm text-gray-600 dark:text-gray-400">{t('No Objection Certificate')}</p>
                                </div>
                              </div>
                            </div>
                            <div className="mt-4">
                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="outline" className="w-full">
                                    <Download className="mr-2 h-4 w-4" />
                                    {t('Download')}
                                    <ChevronDown className="ml-2 h-4 w-4" />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-full">
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('noc', 'pdf')}>
                                    {t('PDF')}
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleDocumentDownload('noc', 'doc')}>
                                    {t('DOC')}
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          </CardContent>
                        </Card>
                      )}
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>

            {/* Documents Tab */}
            <TabsContent value="documents">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Documents')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {employee.employee?.documents && employee.employee.documents.length > 0 ? (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {employee.employee.documents.map((document: any) => (
                        <Card key={document.id} className="border">
                          <CardContent className="p-4">
                            <div className="flex items-start justify-between">
                              <div className="flex items-center">
                                <FileText className="h-8 w-8 mr-3 text-gray-500 dark:text-gray-400" />
                                <div>
                                  <h4 className="font-medium text-gray-900 dark:text-gray-100">{document.document_type?.name}</h4>
                                  <p className="text-sm text-gray-600 dark:text-gray-400">
                                    {document.expiry_date ? `${t('Expires')}: ${window.appSettings?.formatDateTimeSimple(document.expiry_date, false) || new Date(document.expiry_date).toLocaleDateString()}` : t('No expiry date')}
                                  </p>
                                  <div className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-2 ${document.verification_status === 'verified'
                                    ? 'bg-green-50 text-green-700 ring-green-600/20'
                                    : document.verification_status === 'rejected'
                                      ? 'bg-red-50 text-red-700 ring-red-600/20'
                                      : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
                                    }`}>
                                    {document.verification_status === 'verified'
                                      ? t('Verified')
                                      : document.verification_status === 'rejected'
                                        ? t('Rejected')
                                        : t('Pending')}
                                  </div>
                                </div>
                              </div>
                              <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={() => window.open(route('hr.employees.documents.download', [employee.id, document.id]), '_blank')}>
                                  <Download className="h-4 w-4" />
                                </Button>
                                {hasPermission(permissions, 'edit-employees') && (
                                  <Button variant="outline" size="sm" onClick={() => handleDeleteDocument(document.id)}>
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                  </Button>
                                )}
                                {hasPermission(permissions, 'edit-employees') && document.verification_status === 'pending' && (
                                  <>
                                    <Button
                                      variant="outline"
                                      size="sm"
                                      onClick={() => handleDocumentVerification(document.id, 'verified')}
                                      className="text-green-600 hover:text-green-700"
                                    >
                                      <Check className="h-4 w-4" />
                                    </Button>
                                    <Button
                                      variant="outline"
                                      size="sm"
                                      onClick={() => handleDocumentVerification(document.id, 'rejected')}
                                      className="text-red-600 hover:text-red-700"
                                    >
                                      <X className="h-4 w-4" />
                                    </Button>
                                  </>
                                )}
                              </div>
                            </div>
                          </CardContent>
                        </Card>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">
                      {t('No documents found')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </div>

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={employee?.name || ''}
        entityName="employee"
      />
    </PageTemplate>
  );
}