import { useState } from '@wordpress/element';

const CategoryTree = ({ sections, filters, togleInArray }) => {
    return (
        <div className="category-tree">
            {sections.map((section) => (
                <CategoryNode 
                    key={section.term_id}
                    node={section}
                    level={0}
                    filters={filters}
                    togleInArray={togleInArray}
                />
            ))}
        </div>
    );
};

const CategoryNode = ({ node, level, filters, togleInArray }) => {
    const [isExpanded, setIsExpanded] = useState(level < 2); // Expandir primeros niveles por defecto

    const hasChildren = node.children && node.children.length > 0;
    const isChecked = filters.sections?.includes(node.slug) || false;

    return (
        <div 
            className={`category-node level-${level} ${hasChildren ? 'has-children' : 'no-children'}`}
        >
            <div className="category-node-header">
                {hasChildren ? (
                    <span 
                        className="toggle-children"
                        onClick={() => setIsExpanded(!isExpanded)}
                        aria-expanded={isExpanded}
                        aria-label={isExpanded ? wp.i18n.__('Collapse section', 'tender-library') : wp.i18n.__('Expand section', 'tender-library')}
                    >
                        {isExpanded ? '−' : '+'}
                    </span>
                ) : (
                    <span className="toggle-children toggle-placeholder" aria-hidden="true"></span>
                )}
                
                <label>
                    <input
                        type="checkbox"
                        checked={isChecked}
                        onChange={() => togleInArray('sections', node.slug)}
                        aria-labelledby={`category-${node.term_id}-label`}
                    />
                    <span id={`category-${node.term_id}-label`}>
                        {node.section_number && (
                            <span className="section-number">{node.section_number}</span>
                        )}
                        {node.name}
                        {node.count > 0 && (
                            <span className="item-count"> ({node.count})</span>
                        )}
                    </span>
                </label>
            </div>

            {hasChildren && isExpanded && (
                <div className="category-children">
                    {node.children.map((child) => (
                        <CategoryNode
                            key={child.term_id}
                            node={child}
                            level={level + 1}
                            filters={filters}
                            togleInArray={togleInArray}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default CategoryTree;
