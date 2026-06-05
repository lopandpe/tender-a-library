import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export default function Edit() {
	return (
		<section {...useBlockProps()}>
			<div className="block-container">
				<h2>{__("Book data", "tender-library")}</h2>
				<ul>
					<li>{__("Author", "tender-library")}: Xxxxx, Xxxx</li>
					<li>
						{__("Publisher", "tender-library")}: Xxxxxxxx
						Ediciones
					</li>
					<li>
						{__("Library section", "tender-library")}:{" "}
						<a href="#" aria-disabled>
							Xxxxxxxxxx
						</a>
					</li>
					<li>{__("Publication year", "tender-library")}: 1936</li>
					<li>
						{__("Signature", "tender-library")}: X.X, XXX - xxx
					</li>
				</ul>
			</div>
		</section>
	);
}
