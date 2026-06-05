import { __, sprintf } from "@wordpress/i18n";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, RangeControl } from "@wordpress/components";

import "./editor.scss";

export default function Edit( { attributes, setAttributes } ) {
	const { booksToShow = 6 } = attributes;
	const blockProps = useBlockProps();
	const previewItems = Array.from( { length: booksToShow }, ( _, index ) => index );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( "Latest books settings", "tender-library" ) }>
					<RangeControl
						label={ __( "Books to show", "tender-library" ) }
						value={ booksToShow }
						onChange={ ( value ) => setAttributes( { booksToShow: value } ) }
						min={ 1 }
						max={ 24 }
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<p className="tender-latest-books-editor__label">
					{ sprintf(
						/* translators: %d: number of books shown */
						__( "Preview: showing the latest %d books.", "tender-library" ),
						booksToShow
					) }
				</p>
				<div className="tender-latest-books-grid">
					{ previewItems.map( ( item ) => (
						<div
							key={ item }
							className="wp-block-tender-a-library-mini-book tender-book-preview tender-latest-books-grid__item"
						>
							<div className="cover tender-latest-books-editor__cover" />
							<div className="book-info">
								<div className="title">
									{ __( "Latest book title", "tender-library" ) }
								</div>
								<div className="author">
									{ __( "Author name", "tender-library" ) }
								</div>
							</div>
							<div className="book-availability">
								<span className="available">
									{ __( "Available", "tender-library" ) }
								</span>
							</div>
						</div>
					) ) }
				</div>
			</section>
		</>
	);
}
