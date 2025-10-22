<?php
add_filter( 'wodgc_sidebar_menu_items', function( $menus ) {
    $epasscard_menu = [
        'type'  => 'link',
        'tab'   => 'wodgc_epasscard',
        'icon'  => 'dashicons-id-alt',
        // phpcs:ignore
        'label' => __( 'Epasscard', 'gift-card-wooxperto-llc' ),
    ];

    // Insert before the last item
    $insert_position = count( $menus ) - 1;
    array_splice( $menus, $insert_position, 0, [ $epasscard_menu ] );

    return $menus;
});