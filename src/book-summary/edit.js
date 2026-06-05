import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export default function Edit() {
	return (
		<section className="wp-block-tender-book-summary" {...useBlockProps()}>
			<div className="block-container">
				<h2>{__("Book summary", "tender-library")}</h2>
				<p>
					Lorem ipsum dolor sit amet, consectetur adipiscing elit.
					Integer imperdiet tincidunt lacus molestie malesuada.
					Aliquam dapibus bibendum ipsum commodo sagittis. In eleifend
					nisi non ex pretium, a fermentum massa imperdiet. Integer
					ornare sem ac quam pretium, et placerat nibh eleifend.
					Vivamus fringilla purus eget lacinia blandit. Phasellus
					efficitur massa in molestie hendrerit. Aliquam faucibus
					turpis at magna pretium eleifend. Suspendisse eros nulla,
					egestas eu tortor iaculis, consequat tempus massa.
				</p>
				<p>
					Donec nec ligula id nunc efficitur facilisis. Donec ut
					ligula ac enim fringilla tincidunt. Donec euismod, nisi a
					convallis facilisis, felis erat vehicula augue, nec varius
					nunc est in nunc. Nulla facilisi. Donec at dui sed enim
					efficitur sodales. Donec non ligula et odio finibus
					bibendum. Donec ut ligula ac enim fringilla tincidunt. Donec
					euismod, nisi a convallis facilisis, felis erat vehicula
					augue, nec varius nunc est in nunc.
				</p>
			</div>
		</section>
	);
}
