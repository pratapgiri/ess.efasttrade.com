import { useMemo, useState } from 'react';

export function useClaimPagination<T>(items: T[], perPage = 10) {
    const [currentPage, setCurrentPage] = useState(1);

    const totalPages = Math.max(1, Math.ceil(items.length / perPage));

    const safePage = Math.min(currentPage, totalPages);

    const paginatedItems = useMemo(() => {
        const start = (safePage - 1) * perPage;
        return items.slice(start, start + perPage);
    }, [items, safePage, perPage]);

    const resetPage = () => setCurrentPage(1);

    return {
        currentPage: safePage,
        setCurrentPage,
        totalPages,
        paginatedItems,
        perPage,
        from: items.length === 0 ? 0 : (safePage - 1) * perPage + 1,
        resetPage,
    };
}
