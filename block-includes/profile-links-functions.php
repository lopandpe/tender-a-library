<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_profile_links_render_callback( $attributes, $content = '', $block = null ) {
    // Página de perfil / login desde la opción
    $profile_page_option = get_option( 'tal_profile_page' );

    if ( $profile_page_option ) {
        // Asumo que guardas un ID de página; si guardas una URL, puedes usar directamente $profile_page_option.
        $url = get_permalink( (int) $profile_page_option );
    } else {
        // Fallback razonable si no hay opción configurada
        $url = home_url( '/' );
    }

    // Texto según estado de login (multiidioma, base en inglés)
    if ( is_user_logged_in() ) {
        $label       = get_the_title( $profile_page_option );
        $extra_class = 'menu-item-auth--profile';
    } else {
        $label       = esc_html__( 'Login', 'tender-a-library' );
        $extra_class = 'menu-item-auth--login';
    }

    // Icono SVG
    $icon_svg = '
        <span class="menu-item-auth__icon" aria-hidden="true">
            <svg class="menu-item-auth__icon-svg" xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M12 12a5 5 0 1 0-5-5 5.006 5.006 0 0 0 5 5Zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5Z" />
            </svg>
        </span>';

    // <li> compatible con core/navigation
    $item_html = sprintf(
        '<li class="wp-block-navigation-item menu-item-auth tender-auth-link-block %3$s">' .
            '<a href="%1$s" class="wp-block-navigation-item__content menu-item-auth__link">' .
                '%4$s' .
                '<span class="menu-item-auth__label">%2$s</span>
            </a>' .
        '</li>',
        esc_url( $url ),
        $label,
        esc_attr( $extra_class ),
        $icon_svg
    );

    return $item_html;
}
