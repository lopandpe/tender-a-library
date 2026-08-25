import { useEffect, useState } from "react";
import qs from 'qs';
import axios from 'axios';

import Filters from "./Filters";
import Results from "./Results";
import Pagination from "./Pagination";

const PER_PAGE_OPTIONS = ["12", "24", "68", "92"];
const MAX_PER_PAGE = 92;

const defaultFilters = {
    q: '',
    sections: [],
    languages: [],
    page: 1,
    per_page: '12',
    orderby: 'date',
    order: 'desc',
};

const normalizePerPage = (value) => {
    const perPage = Number.parseInt(value, 10);

    if (!Number.isFinite(perPage) || perPage < 1) {
        return defaultFilters.per_page;
    }

    return String(Math.min(perPage, MAX_PER_PAGE));
};

const normalizeFilters = (filters) => ({
    ...filters,
    per_page: normalizePerPage(filters.per_page),
});

const App = ({ apiUrl, filtersUrl }) => {
    const [filters, setFilters] = useState(() => normalizeFilters({
        ...defaultFilters,
        ...qs.parse(window.location.search, { ignoreQueryPrefix: true }),
    }));
    const [options, setOptions] = useState({ sections: [], languages: [] });
    const [pagination, setPagination] = useState({ pages: 0, total: 0 });
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        axios.get(filtersUrl)
            .then(response => {
                setOptions(response.data);
            })
            .catch(error => {
                console.error('Error fetching filter options:', error);
            });
    }, []);

    useEffect(() => {
        setLoading(true);

        // Clean filters: remove empty strings and empty arrays
        const cleanedFilters = Object.fromEntries(
            Object.entries(filters).filter(([key, value]) => {
                if (Array.isArray(value)) return value.length > 0;
                return value !== '' && value !== undefined && value !== null;
            })
        );

        axios.get(apiUrl, { params: cleanedFilters })
            .then(response => {
                setResults(response.data.results);
                setPagination({
                    page: response.data.page,
                    total: response.data.total_pages,
                });
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
            })
            .finally(() => {
                setLoading(false);
            });

        const queryString = qs.stringify(filters, { addQueryPrefix: true });
        window.history.replaceState({}, '', queryString);

    }, [filters]);

    useEffect(() => {
        setFilters((prev) => normalizeFilters({
            ...defaultFilters,
            ...prev,
            sections: Array.isArray(prev.sections)
                ? prev.sections
                : prev.sections
                    ? [prev.sections]
                    : [],
            languages: Array.isArray(prev.languages)
                ? prev.languages
                : prev.languages
                    ? [prev.languages]
                    : [],
        }));
        // Only run on mount
        // eslint-disable-next-line
    }, []);

    return (
        <div className="tender-book-search">
            <Filters filters={filters} setFilters={setFilters} options={options} perPageOptions={PER_PAGE_OPTIONS} />
            <Results books={results} loading={loading} />
            <Pagination filters={filters} setFilters={setFilters} pagination={pagination} />
        </div>
    );
}

export default App;
