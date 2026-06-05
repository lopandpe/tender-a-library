import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import "./editor.scss";

export default function Edit() {
    const blockProps = useBlockProps( {
        className: 'tender-auth-link-block',
    } );

    return (
        <div { ...blockProps }>
            <span className="tender-auth-link-block__icon-preview" aria-hidden="true">
                {/* Icono preview simple */}
                <svg
                    className="tender-auth-link-block__icon-svg"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    role="img"
                    focusable="false"
                >
                    <path d="M12 12a5 5 0 1 0-5-5 5.006 5.006 0 0 0 5 5Zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5Z" />
                </svg>
				<span className="tender-auth-link-block__label">
					{ __( 'Login / Profile (dynamic)', 'tender-library' ) }
				</span>
            </span>
        </div>
    );
}
