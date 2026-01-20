import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";

export default function Save() {
	return (
		<section {...useBlockProps.save()}>
			<div className="block-container">
				<h2>{__("Hola Mundo!")}</h2>
			</div>
		</section>
	);
}
