<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ae_agendamentos" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ae_dias_bloqueados" );

$turnos = get_posts(
	array(
		'post_type'      => 'ae_turno',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

foreach ( $turnos as $turno_id ) {
	wp_delete_post( $turno_id, true );
}
