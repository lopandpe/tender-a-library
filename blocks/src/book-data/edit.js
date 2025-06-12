import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export default function Edit() {
	return (
		<section {...useBlockProps()}>
			<div className="block-container">
				<h2>{__("Book data", "tender-a-library")}</h2>
				<ul>
					<li>{__("Author", "tender-a-library")}: Xxxxx, Xxxx</li>
					<li>
						{__("Publisher", "tender-a-library")}: Xxxxxxxx
						Ediciones
					</li>
					<li>
						{__("Library section", "tender-a-library")}:{" "}
						<a href="#" aria-disabled>
							Xxxxxxxxxx
						</a>
					</li>
					<li>{__("Publication year", "tender-a-library")}: 1936</li>
					<li>
						{__("Signature", "tender-a-library")}: X.X, XXX - xxx
					</li>
				</ul>
			</div>
		</section>
	);
}
