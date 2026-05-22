/**
 * Pagination component with dark mode support
 */
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface PaginationProps {
  from?: number;
  to?: number;
  total?: number;
  links?: any[];
  currentPage?: number;
  lastPage?: number;
  entityName?: string;
  onPageChange?: (url: string) => void;
  className?: string;
}

export function Pagination({
  from = 0,
  to = 0,
  total = 0,
  links = [],
  currentPage,
  lastPage,
  entityName = 'items',
  onPageChange,
  className = '',
}: PaginationProps) {
  const { t } = useTranslation();

  const buildUrlPreservingCurrentQuery = (url: string): string => {
    if (!url || typeof window === 'undefined') {
      return url;
    }

    const target = new URL(url, window.location.origin);
    const current = new URL(window.location.href);

    // Keep existing active filters/sort from current page.
    current.searchParams.forEach((value, key) => {
      if (!target.searchParams.has(key)) {
        target.searchParams.set(key, value);
      }
    });

    return `${target.pathname}${target.search}`;
  };

  const handlePageChange = (url: string) => {
    const resolvedUrl = buildUrlPreservingCurrentQuery(url);
    if (onPageChange) {
      onPageChange(resolvedUrl);
    } else if (resolvedUrl) {
      window.location.href = resolvedUrl;
    }
  };

  return (
    <div className={cn(
      "p-4 border-t dark:border-gray-700 flex items-center justify-between dark:bg-gray-900",
      className
    )}>
      <div className="text-sm text-muted-foreground dark:text-gray-300">
        {t("Showing")} <span className="font-medium dark:text-white">{from}</span> {t("to")}{" "}
        <span className="font-medium dark:text-white">{to}</span> {t("of")}{" "}
        <span className="font-medium dark:text-white">{total}</span> {entityName}
      </div>

      <div className="flex gap-1">
        {links && links.length > 0 ? (
          links.map((link: any, i: number) => {
            // Check if the link is "Next" or "Previous" to use text instead of icon
            const isTextLink = link.label === "&laquo; Previous" || link.label === "Next &raquo;";
            const label = link.label.replace("&laquo; ", "").replace(" &raquo;", "");

            return (
              <Button
                key={`pagination-${i}-${link.label}`}
                variant={link.active ? 'default' : 'outline'}
                size={isTextLink ? "sm" : "icon"}
                className={isTextLink ? "px-3" : "h-8 w-8"}
                disabled={!link.url}
                onClick={() => link.url && handlePageChange(link.url)}
              >
                {isTextLink ? label : <span dangerouslySetInnerHTML={{ __html: link.label }} />}
              </Button>
            );
          })
        ) : (
          // Simple pagination if links are not available
          currentPage && lastPage && lastPage > 1 && (
            <>
              <Button
                variant="outline"
                size="sm"
                disabled={currentPage <= 1}
                onClick={() => handlePageChange(`?page=${currentPage - 1}`)}
              >
                {t("Previous")}
              </Button>
              <span className="px-3 py-1 dark:text-white">
                {currentPage} of {lastPage}
              </span>
              <Button
                variant="outline"
                size="sm"
                disabled={currentPage >= lastPage}
                onClick={() => handlePageChange(`?page=${currentPage + 1}`)}
              >
                {t("Next")}
              </Button>
            </>
          )
        )}
      </div>
    </div>
  );
}