// pages/hr/performance/employee-reviews/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from 'react-i18next';
import { ClipboardList, ArrowLeft, Star } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';

export default function ShowEmployeeReview() {
  const { t } = useTranslation();
  const { review, auth } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'scheduled':
        return <Badge variant="outline" className="bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">{t('Scheduled')}</Badge>;
      case 'in_progress':
        return <Badge variant="outline" className="bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">{t('In Progress')}</Badge>;
      case 'completed':
        return <Badge variant="outline" className="bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20">{t('Completed')}</Badge>;
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  const handleBack = () => {
    router.visit(route('hr.performance.employee-reviews.index'));
  };

  const handleConductReview = () => {
    router.visit(route('hr.performance.employee-reviews.conduct', review.id));
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Performance'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Employee Reviews'), href: route('hr.performance.employee-reviews.index') },
    { title: t('View Review') }
  ];

  // Group ratings by category
  const ratingsByCategory = review.ratings?.reduce((acc: any, rating: any) => {
    const categoryId = rating.indicator?.category?.id || 'uncategorized';
    const categoryName = rating.indicator?.category?.name || 'Uncategorized';

    if (!acc[categoryId]) {
      acc[categoryId] = {
        name: categoryName,
        ratings: []
      };
    }

    acc[categoryId].ratings.push(rating);
    return acc;
  }, {});

  return (
    <PageTemplate
      title={t("Review Details")}
      url={`/hr/performance/employee-reviews/${review.id}`}
      breadcrumbs={breadcrumbs}
      actions={[
        {
          label: t('Back'),
          icon: <ArrowLeft className="h-4 w-4 mr-2" />,
          variant: 'outline',
          onClick: handleBack
        },
        ...(review.status !== 'completed' && hasPermission(permissions, 'edit-employee-reviews') ? [{
          label: t('Conduct Review'),
          icon: <ClipboardList className="h-4 w-4 mr-2" />,
          variant: 'default',
          onClick: handleConductReview
        }] : [])
      ]}
    >
      <div className="space-y-6">

        {/* Review Details */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{t('Review Information')}</CardTitle>
            <CardDescription>{t('Details about this performance review')}</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
              <div className="space-y-6">
                <div>
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wide mb-2">{t('Employee')}</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {review.employee?.name}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400  tracking-wide mb-2">{t('Reviewer')}</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {review.reviewer?.name}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400  tracking-wide mb-2">{t('Review Cycle')}</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {review.review_cycle?.name}
                  </p>
                </div>
              </div>

              <div className="space-y-6">

                <div>
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400  tracking-wide mb-2">{t('Review Date')}</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {review.review_date ? (window.appSettings?.formatDateTimeSimple(review.review_date, false) || new Date(review.review_date).toLocaleString()) : '-'}
                  </p>
                </div>

                <div>
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400  tracking-wide mb-2">{t('Status')}</p>
                  <div className="mt-1">
                    {getStatusBadge(review.status)}
                  </div>
                </div>
              </div>
            </div>

            {review.status === 'completed' && (
              <div className="mt-6 pt-6 border-t">
                <div className="flex items-center justify-between">
                  <p className="text-base font-semibold text-gray-900 dark:text-gray-100">{t('Overall Rating')}</p>
                  <div className="flex items-center">
                    <span className="text-2xl font-bold text-gray-900 dark:text-gray-100 mr-2">{review.overall_rating?.toFixed(1)}</span>
                    <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                  </div>
                </div>

                {review.comments && (
                  <div className="mt-4">
                    <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{t('Comments')}</p>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">{review.comments}</p>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Ratings */}
        {review.status === 'completed' && (
          <Card>
            <CardHeader>
              <CardTitle className="text-lg font-semibold">{t('Performance Ratings')}</CardTitle>
              <CardDescription>{t('Individual ratings for each performance indicator')}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-6">
                {Object.values(ratingsByCategory || {}).map((category: any) => (
                  <div key={category.name} className="space-y-4">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">{category.name}</h3>
                    <div className="space-y-4">
                      {category.ratings.map((rating: any) => (
                        <div key={rating.id} className="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                          <div className="flex items-start justify-between">
                            <div className="flex-1">
                              <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">{rating.indicator?.name}</h4>
                              <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {rating.indicator?.description || t('No description')}
                              </p>
                              {rating.indicator?.measurement_unit && (
                                <Badge variant="outline" className="mt-2">
                                  {rating.indicator?.measurement_unit}
                                </Badge>
                              )}
                            </div>
                            <div className="flex items-center ml-4">
                              <span className="text-xl font-bold text-gray-900 dark:text-gray-100 mr-2">{rating.rating.toFixed(1)}</span>
                              <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                            </div>
                          </div>

                          {rating.comments && (
                            <div className="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                              <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{t('Comments')}</p>
                              <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">{rating.comments}</p>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </PageTemplate>
  );
}