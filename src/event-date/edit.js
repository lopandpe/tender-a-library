import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export default function Edit() {
	return (
		<section className="wp-block-tender-event-date" {...useBlockProps()}>
			
			<div class="block-container">

				<p class="tender-event-date">
					<span class="tender-event-date__dt">
						XXX mmmmmm, YYYY 18:00				
					</span>
				</p>

				<p class="tender-event-date__recurrent">
					<span class="tender-event-date__rec">Recurring event, every xxxxxx</span>
				</p>
				<p class="tender-event-date__start">
					Since _startdate_ until _enddate_				
				</p>
				
			</div>
		</section>
	);
}
