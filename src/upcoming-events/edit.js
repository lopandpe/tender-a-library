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
				<PanelBody title={ __( 'Events adjustments', 'tender-library' ) }>
					<RadioControl
						label={ __( 'Visualization mode', 'tender-library' ) }
						selected={ mode }
						options={ [
							{ label: __( 'Monthly calendar', 'tender-library' ), value: 'calendar' },
							{ label: __( 'List of upcoming events', 'tender-library' ), value: 'list' },
						] }
						onChange={ ( value ) => setAttributes( { mode: value } ) }
					/>

					{ mode === 'list' && (
						<RangeControl
							label={ __( 'Events to show', 'tender-library' ) }
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
					<p>{ __( 'Preview: a monthly calendar of events will be displayed.', 'tender-library' ) }</p>
				) : (
					<p>
						{ __( 'Preview: a list of the next', 'tender-library' ) } { limit } { __( 'events will be displayed.', 'tender-library' ) }
					</p>
				) }
			</div>
		</>
	);
}
