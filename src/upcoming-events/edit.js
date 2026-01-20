import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, RadioControl, RangeControl } from "@wordpress/components";
import "./editor.scss";

export default function Edit( props ) {
	const { attributes, setAttributes } = props;
    const { mode, limit } = attributes; 

    const blockProps = useBlockProps();
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Events adjustments', 'tender-a-library' ) }>
					<RadioControl
						label={ __( 'Visualization mode', 'tender-a-library' ) }
						selected={ mode }
						options={ [
							{ label: __( 'Monthly calendar', 'tender-a-library' ), value: 'calendar' },
							{ label: __( 'List of upcoming events', 'tender-a-library' ), value: 'list' },
						] }
						onChange={ ( value ) => setAttributes( { mode: value } ) }
					/>

					{ mode === 'list' && (
						<RangeControl
							label={ __( 'Events to show', 'tender-a-library' ) }
							value={ limit }
							onChange={ ( value ) => setAttributes( { limit: value } ) }
							min={ 1 }
							max={ 50 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ mode === 'calendar' ? (
					<p>{ __( 'Preview: a monthly calendar of events will be displayed.', 'tender-a-library' ) }</p>
				) : (
					<p>
						{ printf(__( 'Preview: a list of the next %s events will be displayed.', 'tender-a-library' ), limit) }
					</p>
				) }
			</div>
		</>
	);
}
