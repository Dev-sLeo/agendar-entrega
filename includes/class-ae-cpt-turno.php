<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT interno que representa um turno de entrega (ex: "Manhã 10h-14h").
 * Não é público — usado apenas como registro configurável pelo admin.
 */
class AE_CPT_Turno {

	const POST_TYPE = 'ae_turno';

	public function __construct() {
		add_action( 'init', array( $this, 'registrar_cpt' ) );
	}

	public function registrar_cpt() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Turnos', 'agendar-entregas' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_menu'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Retorna todos os turnos cadastrados, com os meta campos já resolvidos.
	 *
	 * @return array Lista de objetos: id, nome, hora_inicio, hora_fim, limite_por_dia, dias_semana[]
	 */
	public static function listar_turnos() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => 'hora_inicio',
				'order'          => 'ASC',
			)
		);

		$turnos = array();
		foreach ( $posts as $post ) {
			$turnos[] = self::obter_turno( $post->ID );
		}

		return $turnos;
	}

	public static function obter_turno( $turno_id ) {
		$post = get_post( $turno_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return (object) array(
			'id'              => $post->ID,
			'nome'            => $post->post_title,
			'hora_inicio'     => get_post_meta( $post->ID, 'hora_inicio', true ),
			'hora_fim'        => get_post_meta( $post->ID, 'hora_fim', true ),
			'limite_por_dia'  => (int) get_post_meta( $post->ID, 'limite_por_dia', true ),
			'dias_semana'     => (array) get_post_meta( $post->ID, 'dias_semana', true ),
			// IDs de taxa (method_id:instance_id) dos métodos de entrega aos quais
			// este turno pertence. Vazio = disponível para qualquer método.
			'metodos_entrega' => (array) get_post_meta( $post->ID, 'metodos_entrega', true ),
		);
	}

	/**
	 * Turnos configurados para o dia da semana de $data (0=domingo ... 6=sábado).
	 */
	public static function listar_turnos_do_dia( $data ) {
		// gmdate(), não wp_date(): ver o mesmo comentário em
		// AE_Dias_Bloqueados::esta_dia_semana_bloqueado().
		$dia_semana = (int) gmdate( 'w', strtotime( $data ) );

		return array_values(
			array_filter(
				self::listar_turnos(),
				function ( $turno ) use ( $dia_semana ) {
					return in_array( $dia_semana, array_map( 'intval', $turno->dias_semana ), true );
				}
			)
		);
	}
}
