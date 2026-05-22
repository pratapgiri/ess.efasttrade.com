// pages/hr/training/programs/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Edit, Users, Calendar, BarChart } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

export default function TrainingProgramShow() {
  const { t } = useTranslation();
  const { trainingProgram, statistics } = usePage().props as any;
  
  const handleBack = () => {
    router.get(route('hr.training-programs.index'));
  };
  
  const handleEdit = () => {
    router.get(route('hr.training-programs.index'), {}, {
      onSuccess: () => {
        // Trigger edit modal - this would need to be implemented
        // For now, just redirect back to index
      }
    });
  };
  
  const pageActions = [
    {
      label: t('Back'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleBack
    },
    {
      label: t('Edit'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleEdit
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-programs.index') },
    { title: t('Training Programs'), href: route('hr.training-programs.index') },
    { title: trainingProgram.name }
  ];

  const statusClasses = {
    'draft': 'bg-gray-50 text-gray-700 ring-gray-600/20',
    'active': 'bg-green-50 text-green-700 ring-green-600/20',
    'completed': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
  };

  return (
    <PageTemplate 
      title={trainingProgram.name}
      url={`/hr/training/programs/${trainingProgram.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Program Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{t('Program Details')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Training Type')}</p>
              <p className="text-sm">{trainingProgram.training_type?.name || '-'}</p>
            </div>
            
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Description')}</p>
              <p className="text-sm">{trainingProgram.description || '-'}</p>
            </div>
            
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Prerequisites')}</p>
              <p className="text-sm">{trainingProgram.prerequisites || '-'}</p>
            </div>
            
            <div className="flex flex-wrap gap-2">
              {trainingProgram.is_mandatory && (
                <Badge variant="outline" className="text-xs bg-red-50 text-red-700">
                  {t('Mandatory')}
                </Badge>
              )}
              {trainingProgram.is_self_enrollment && (
                <Badge variant="outline" className="text-xs bg-blue-50 text-blue-700">
                  {t('Self-Enrollment')}
                </Badge>
              )}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{t('Program Info')}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Status')}</p>
              <div>
                <Badge variant="outline" className={`text-xs ring-1 ring-inset ${statusClasses[trainingProgram.status] || ''}`}>
                  {trainingProgram.status.charAt(0).toUpperCase() + trainingProgram.status.slice(1)}
                </Badge>
              </div>
            </div>
            
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Duration')}</p>
              <p className="text-sm">{trainingProgram.duration ? `${trainingProgram.duration} ${t('hours')}` : '-'}</p>
            </div>
            
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Cost')}</p>
              <p className="text-sm">{trainingProgram.cost ? window.appSettings?.formatCurrency(parseFloat(trainingProgram.cost)) : '-'}</p>
            </div>
            
            <div>
              <p className="text-sm font-medium text-muted-foreground mb-1">{t('Capacity')}</p>
              <p className="text-sm">{trainingProgram.capacity || '-'}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{t('Total Sessions')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{statistics.totalSessions}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{t('Completed Sessions')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-green-600">{statistics.completedSessions}</p>
            <p className="text-xs text-muted-foreground mt-1">
              {statistics.totalSessions > 0 ? Math.round((statistics.completedSessions / statistics.totalSessions) * 100) : 0}% {t('completion rate')}
            </p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{t('Total Employees')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{statistics.totalTrainings}</p>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">{t('Completed Trainings')}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold text-blue-600">{statistics.completedTrainings}</p>
            <p className="text-xs text-muted-foreground mt-1">
              {statistics.totalTrainings > 0 ? Math.round((statistics.completedTrainings / statistics.totalTrainings) * 100) : 0}% {t('completion rate')}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Progress Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{t('Session Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex justify-between text-sm font-medium">
                <span>{t('Sessions Completed')}</span>
                <span>{statistics.completedSessions || 0}/{statistics.totalSessions || 0}</span>
              </div>
              <Progress value={statistics.totalSessions > 0 ? (statistics.completedSessions / statistics.totalSessions) * 100 : 0} className="h-2" />
              <p className="text-xs text-muted-foreground">
                {statistics.totalSessions > 0 ? Math.round((statistics.completedSessions / statistics.totalSessions) * 100) : 0}% {t('of sessions completed')}
              </p>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{t('Employee Progress')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <div className="flex justify-between text-sm font-medium">
                <span>{t('Employees Completed')}</span>
                <span>{statistics.completedTrainings || 0}/{statistics.totalTrainings || 0}</span>
              </div>
              <Progress value={statistics.totalTrainings > 0 ? (statistics.completedTrainings / statistics.totalTrainings) * 100 : 0} className="h-2" />
              <p className="text-xs text-muted-foreground">
                {statistics.totalTrainings > 0 ? Math.round((statistics.completedTrainings / statistics.totalTrainings) * 100) : 0}% {t('of employees completed')}
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}