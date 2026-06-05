import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export default function Edit() {
	return (
		<section {...useBlockProps()}>
			<div className="block-container">
				<h2>{__("Book search", "tender-library")}</h2>
				<p>{__("This block can only be seen on the frontend.", "tender-library")}</p>
			</div>
		</section>
	);
}
