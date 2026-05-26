import { useEffect, useState } from "react";
import qs from 'qs';
import axios from 'axios';

import Filters from "./Filters";
import Results from "./Results";
import Pagination from "./Pagination";

const defaultFilters = {
    q: '',
    sections: [],
    languages: [],
    page: 1,
    per_page: '12',
    orderby: 'date',
    order: 'desc',
};

const App = ({ apiUrl, filtersUrl }) => {
    const [filters, setFilters] = useState(() => ({
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
        setFilters((prev) => ({
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
            <Filters filters={filters} setFilters={setFilters} options={options} />
            <Results books={results} loading={loading} />
            <Pagination filters={filters} setFilters={setFilters} pagination={pagination} />
        </div>
    );
}

export default App;
