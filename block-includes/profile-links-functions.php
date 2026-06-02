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
        esc_html( $label ),
        esc_attr( $extra_class ),
        $icon_svg
    );

    $item_html .= tal_get_users_list_menu_item_html();

    return $item_html;
}


function tal_get_users_list_menu_item_html()
{
    if (!is_user_logged_in() || !function_exists('tal_can_see_users_list') || !tal_can_see_users_list()) {
        return '';
    }

    $users_page_id = absint(get_option('tal_users_list_page'));
    $users_url = $users_page_id ? get_permalink($users_page_id) : '';

    if (!$users_url) {
        return '';
    }

    return sprintf(
        '<li class="wp-block-navigation-item menu-item-auth tender-users-link-block">' .
            '<a href="%1$s" class="wp-block-navigation-item__content menu-item-auth__link">' .
                '<span class="menu-item-auth__label">%2$s</span>' .
            '</a>' .
        '</li>',
        esc_url($users_url),
        esc_html(get_the_title($users_page_id) ?: __('Users', 'tender-a-library'))
    );
}

function tal_add_users_link_to_classic_main_menu($items, $args)
{
    if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
        return $items;
    }

    $main_locations = array('primary', 'main', 'menu-1', 'header');
    $theme_location = isset($args->theme_location) ? (string) $args->theme_location : '';

    if (!in_array($theme_location, $main_locations, true)) {
        return $items;
    }

    $users_item = tal_get_users_list_menu_item_html();
    if (!$users_item || false !== strpos($items, 'tender-users-link-block')) {
        return $items;
    }

    return $items . $users_item;
}
add_filter('wp_nav_menu_items', 'tal_add_users_link_to_classic_main_menu', 10, 2);
