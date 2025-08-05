import CategoryTree from "./CategoryTree";
import { useState } from "@wordpress/element";

const Filters = ({ filters, setFilters, options }) => {
	const [active, setActive] = useState(false);

	const update = (key, value) => {
		setFilters((prev) => ({
			...prev,
			[key]: value,
		}));
	};

	const resetFilters = () => {
		setFilters({
			q: "",
			sections: [],
			languages: [],
			page: 1,
			per_page: "12",
			orderby: "date",
			order: "desc",
		});
	};

	const togleInArray = (key, slug) => {
		const current = filters[key] || [];
		const updated = current.includes(slug)
			? current.filter((item) => item !== slug)
			: [...current, slug];
		update(key, updated);
	};
	// Provide defaults to avoid errors
	const sections = Array.isArray(options.sections) ? options.sections : [];
	const languages = Array.isArray(options.languages) ? options.languages : [];

	const HIDDEN_FILTERS = ["page", "per_page", "orderby", "order"];

	const isActiveFilter = (key, value) => {
		if (Array.isArray(value)) return value.length > 0;
		if (typeof value === "string") return value.trim() !== "";
		if (typeof value === "number") return false; // p.ej. no mostramos página
		return !!value;
	};

	const FILTER_LABELS = {
		q: wp.i18n.__("Search", "tender-a-library"),
		sections: wp.i18n.__("Sections", "tender-a-library"),
		languages: wp.i18n.__("Languages", "tender-a-library"),
		page: wp.i18n.__("Page", "tender-a-library"),
		per_page: wp.i18n.__("Per Page", "tender-a-library"),
		orderby: wp.i18n.__("Order By", "tender-a-library"),
		order: wp.i18n.__("Order", "tender-a-library"),
	};

	const getSectionName = (slug) => {
		// Recursivo: busca en todo el árbol
		const find = (nodes) => {
			for (const node of nodes) {
				if (node.slug === slug) return node.name;
				if (node.children && node.children.length) {
					const found = find(node.children);
					if (found) return found;
				}
			}
			return slug; // fallback
		};
		return find(sections);
	};

	const getLanguageName = (slug) => {
		const lang = languages.find((l) => l.slug === slug);
		return lang ? lang.name : slug;
	};

	const hasActiveFilters = Object.entries(filters)
		.filter(([key]) => !HIDDEN_FILTERS.includes(key))
		.some(([key, value]) => isActiveFilter(key, value));

	return (
		<aside className="tender-filters">
			<div className="tender-filters-summary">
				<div
					className={`tender-filters-toggle ${
						active ? "active" : ""
					}`}
					onClick={() => setActive((prev) => !prev)}
					aria-label={wp.i18n.__("Show filters", "tender-a-library")}
				>
					{wp.i18n.__("Show filters", "tender-a-library")}
				</div>
				<div id="selected-filters">
					{Object.entries(filters)
						.filter(
							([key, value]) =>
								!HIDDEN_FILTERS.includes(key) &&
								isActiveFilter(key, value),
						)
						.map(([key, value]) => {
							let prettyValue = value;
							if (key === "sections") {
								prettyValue = value
									.map(getSectionName)
									.join(", ");
							} else if (key === "languages") {
								prettyValue = value
									.map(getLanguageName)
									.join(", ");
							}
							return (
								<span key={key}>
									<strong>
										{FILTER_LABELS[key] || key}:
									</strong>{" "}
									{Array.isArray(value) ? prettyValue : value}
								</span>
							);
						})}
				</div>
				{hasActiveFilters && (
					<button
						className="tender-filters-reset tal-button"
						onClick={resetFilters}
						aria-label={wp.i18n.__(
							"Reset all filters",
							"tender-a-library",
						)}
					>
						{wp.i18n.__("Reset Filters", "tender-a-library")}
					</button>
				)}
			</div>

			<div id="filters-wrapper" className={active ? "active" : ""}>
				<div
					className="tender-filters-close"
					onClick={() => setActive(false)}
					aria-label={wp.i18n.__("Close filters", "tender-a-library")}
				>
					{wp.i18n.__("Hide filters", "tender-a-library")}
				</div>
				<h2>{wp.i18n.__("Filters", "tender-a-library")}</h2>
				
				{hasActiveFilters && (
					<button
						className="tender-filters-reset tal-button"
						onClick={resetFilters}
						aria-label={wp.i18n.__(
							"Reset all filters",
							"tender-a-library",
						)}
					>
						{wp.i18n.__("Reset Filters", "tender-a-library")}
					</button>
				)}
				<fieldset className="tender-fieldset">
					<legend>{wp.i18n.__("Search", "tender-a-library")}</legend>
					<input
						type="text"
						placeholder={wp.i18n.__(
							"Search by title, author, etc",
							"tender-a-library",
						)}
						value={filters.q ?? ""}
						onChange={(e) => update("q", e.target.value)}
					/>
				</fieldset>
				<fieldset className="tender-fieldset">
					<legend>
						{wp.i18n.__("Sections", "tender-a-library")}
					</legend>
					<CategoryTree
						sections={sections}
						filters={filters}
						togleInArray={togleInArray}
					/>
				</fieldset>
				<fieldset className="tender-fieldset">
					<legend>
						{wp.i18n.__("Languages", "tender-a-library")}
					</legend>
					{languages.map((language) => (
						<label key={language.term_id}>
							<input
								type="checkbox"
								checked={
									filters.languages?.includes(
										language.slug,
									) || false
								}
								onChange={() =>
									togleInArray("languages", language.slug)
								}
							/>
							{language.name}
						</label>
					))}
				</fieldset>
				<fieldset className="tender-fieldset">
					<legend>
						{wp.i18n.__("Books per page", "tender-a-library")}
					</legend>
					<select
						value={filters.per_page || ""}
						onChange={(e) => update("per_page", e.target.value)}
					>
						<option value="12" defaultChecked>
							12
						</option>
						<option value="24">24</option>
						<option value="68">68</option>
						<option value="92">92</option>
					</select>
				</fieldset>
			</div>
		</aside>
	);
};
export default Filters;
