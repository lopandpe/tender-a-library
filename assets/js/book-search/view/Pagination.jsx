const Pagination = ({ filters, setFilters, pagination }) => {
    const currentPage = parseInt(filters.page || 1);
    if (pagination.total <= 1) {
        return null;
    }

    return (
        <nav className="tender-pagination">
            <button
                disabled={currentPage === 1}
                onClick={() => setFilters({ ...filters, page: currentPage - 1 })}
            >
                {wp.i18n.__('Previous', 'tender-library')}
            </button>
            <span>
                {wp.i18n.sprintf(
                    /* translators: 1: current page, 2: total pages */
                    wp.i18n.__('Page %1$d of %2$d', 'tender-library'),
                    currentPage,
                    pagination.total
                )}
            </span>
            <button
                disabled={currentPage === pagination.total}
                onClick={() => setFilters({ ...filters, page: currentPage + 1 })}
            >
                {wp.i18n.__('Next', 'tender-library')}
            </button>
        </nav>
    );
}

export default Pagination;
