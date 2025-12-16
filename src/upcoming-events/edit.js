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
				<PanelBody title={ __( 'Ajustes de eventos', 'tender-a-library' ) }>
					<RadioControl
						label={ __( 'Modo de visualización', 'tender-a-library' ) }
						selected={ mode }
						options={ [
							{ label: __( 'Calendario mensual', 'tender-a-library' ), value: 'calendar' },
							{ label: __( 'Lista de próximos eventos', 'tender-a-library' ), value: 'list' },
						] }
						onChange={ ( value ) => setAttributes( { mode: value } ) }
					/>

					{ mode === 'list' && (
						<RangeControl
							label={ __( 'Número de eventos', 'tender-a-library' ) }
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
					<p>{ __( 'Vista previa: se mostrará un calendario mensual de eventos.', 'tender-a-library' ) }</p>
				) : (
					<p>
						{ __( 'Vista previa: se mostrará un listado de los próximos', 'tender-a-library' ) } { limit } { __( 'eventos.', 'tender-a-library' ) }
					</p>
				) }
			</div>
		</>
	);
}
