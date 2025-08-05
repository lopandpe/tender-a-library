import React, { useState } from "react";
import { createRoot } from "react-dom/client";
import App from "./view/App";

document.addEventListener("DOMContentLoaded", () => {
    const rootElement = document.getElementById("library-search-root");
    if (rootElement) {
        const apiUrl = rootElement.getAttribute("data-api-url");
        const filtersUrl = rootElement.getAttribute("data-filters-url");

        const RootWithI18n = () => {
            // Este estado cambia cuando se dispara el evento de traducción
            const [ready, setReady] = useState(false);

            React.useEffect(() => {
                const handler = () => setReady(r => !r);
                document.addEventListener('wp-i18n-init', handler);
                // Dispara también si ya está
                setReady(r => !r);
                return () => document.removeEventListener('wp-i18n-init', handler);
            }, []);

            return <App apiUrl={apiUrl} filtersUrl={filtersUrl} />;
        };

        const root = createRoot(rootElement);
        root.render(<RootWithI18n />);
    }
});
// This file is part of Tender a Library, a WordPress plugin to manage library books.