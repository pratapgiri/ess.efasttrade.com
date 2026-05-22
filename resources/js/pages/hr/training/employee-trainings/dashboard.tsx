// pages/hr/training/employee-trainings/dashboard.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { List, BarChart3 } from 'lucide-react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { format } from 'date-fns';
import { Progress } from '@/components/ui/progress';

export default function EmployeeTrainingDashboard() {
  const { t } = useTranslation();
  const { statistics, programStats, recentCompletions, upcomingTrainings } = usePage().props as any;
  
  const handleViewList = () => {
    router.get(route('hr.employee-trainings.index'));
  };

  // Define page actions
  const pageActions = [
    {
      label: t('List View'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewList
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.employee-trainings.index') },
    { title: t('Training Management'), href: route('hr.employee-trainings.index') },
    { title: t('Employee Trainings'), href: route('hr.employee-trainings.index') },
    { title: t('Dashboard') }
  ];

  return (
    <PageTemplate 
      title={t("Training Dashboard")} 
      url="/hr/training/employee-trainings/dashboard"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Training Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Total Trainings')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{statistics.totalTrainings}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Completed')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-green-600 dark:text-green-400">{statistics.completedTrainings}</p>
            <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
              {statistics.completionRate}% {t('Completion Rate')}
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('In Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">{statistics.inProgressTrainings}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Assigned')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">{statistics.assignedTrainings}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide">{t('Failed')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-red-600 dark:text-red-400">{statistics.failedTrainings}</p>
          </CardContent>
        </Card>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Program Completion Rates */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Program Completion Rates')}</CardTitle>
          </CardHeader>
          <CardContent>
            {programStats && programStats.length > 0 ? (
              <div className="space-y-4">
                {programStats.map((program: any, index: number) => (
                  <div key={index}>
                    <div className="flex justify-between mb-2">
                      <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{program.name}</p>
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{program.completion_rate}%</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Progress 
                        value={program.completion_rate} 
                        className="h-2 flex-1"
                      />
                      <p className="text-xs text-gray-600 dark:text-gray-400">
                        {program.completed}/{program.total}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No program data available')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Recent Completions */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Recent Completions')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentCompletions && recentCompletions.length > 0 ? (
              <div className="space-y-3">
                {recentCompletions.map((training: any) => (
                  <div key={training.id} className="flex items-start justify-between pb-3 border-b last:border-0">
                    <div className="flex-1">
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">{training.employee?.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400">{training.training_program?.name}</p>
                      <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                        {t('Completed')} {window.appSettings?.formatDateTimeSimple(training.completion_date, false) || format(new Date(training.completion_date), 'MMM dd, yyyy')}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{training.score ? `${training.score}%` : '-'}</p>
                      <p className="text-xs">
                        {training.is_passed ? (
                          <span className="text-green-600 dark:text-green-400">{t('Passed')}</span>
                        ) : (
                          <span className="text-red-600 dark:text-red-400">{t('Failed')}</span>
                        )}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No recent completions')}</div>
            )}
          </CardContent>
        </Card>
      </div>
      
      {/* Upcoming Trainings */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg font-semibold text-gray-900 dark:text-gray-100">{t('Upcoming Trainings')}</CardTitle>
        </CardHeader>
        <CardContent>
          {upcomingTrainings && upcomingTrainings.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {upcomingTrainings.map((training: any) => (
                <Card key={training.id} className="border">
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-semibold text-gray-900 dark:text-gray-100">{training.training_program?.name}</CardTitle>
                    <CardDescription className="text-xs">{training.employee?.name}</CardDescription>
                  </CardHeader>
                  <CardContent className="pb-2">
                    <p className="text-xs text-gray-600 dark:text-gray-400">
                      <span className="font-medium">{t('Assigned')}</span> {window.appSettings?.formatDateTimeSimple(training.assigned_date, false) || format(new Date(training.assigned_date), 'MMM dd, yyyy')}
                    </p>
                  </CardContent>
                  <CardFooter>
                    <Button 
                      variant="outline" 
                      size="sm"
                      className="w-full"
                      onClick={() => router.get(route('hr.employee-trainings.show', training.id))}
                    >
                      {t('View Details')}
                    </Button>
                  </CardFooter>
                </Card>
              ))}
            </div>
          ) : (
            <div className="text-center py-12 text-sm text-gray-500 dark:text-gray-400">{t('No upcoming trainings')}</div>
          )}
        </CardContent>
      </Card>
    </PageTemplate>
  );
}