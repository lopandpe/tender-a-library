import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";

export default function Save() {
	return (
		<section
			className="wp-block-mimotic-usp-block"
			{...useBlockProps.save()}
		>
			<div className="block-container">
				<h2>{__("Hola Mundo!")}</h2>
			</div>
		</section>
	);
}
