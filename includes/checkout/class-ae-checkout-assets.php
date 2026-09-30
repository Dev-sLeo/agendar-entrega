<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AE_Checkout_Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_ae_get_turnos', array( $this, 'ajax_get_turnos' ) );
		add_action( 'wp_ajax_nopriv_ae_get_turnos', array( $this, 'ajax_get_turnos' ) );
		add_action( 'wp_ajax_ae_get_datas_disponiveis', array( $this, 'ajax_get_datas_disponiveis' ) );
		add_action( 'wp_ajax_nopriv_ae_get_datas_disponiveis', array( $this, 'ajax_get_datas_disponiveis' ) );
	}

	public function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'ae-flatpickr',
			'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css',
			array(),
			'4.6.13'
		);

		wp_enqueue_style(
			'ae-checkout',
			AE_PLUGIN_URL . 'assets/css/checkout.css',
			array(),
			AE_VERSION
		);

		wp_enqueue_script(
			'ae-flatpickr',
			'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js',
			array(),
			'4.6.13',
			true
		);

		wp_enqueue_script(
			'ae-flatpickr-pt',
			'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/pt.js',
			array( 'ae-flatpickr' ),
			'4.6.13',
			true
		);

		wp_enqueue_script(
			'ae-checkout',
			AE_PLUGIN_URL . 'assets/js/checkout.js',
			array( 'jquery', 'ae-flatpickr', 'ae-flatpickr-pt' ),
			AE_VERSION,
			true
		);

		wp_localize_script(
			'ae-checkout',
			'aeCheckout',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'ae_get_turnos' ),
				'dataMinima' => AE_Disponibilidade::data_minima(),
				'textos'     => array(
					'selecioneData' => __( 'Selecione a data primeiro', 'agendar-entregas' ),
					'semTurno'      => __( 'Nenhum turno disponível para esta data', 'agendar-entregas' ),
				),
			)
		);
	}

	/**
	 * Datas com pelo menos um turno com vaga, para o datepicker do checkout
	 * desabilitar visualmente os dias lotados/bloqueados.
	 */
	public function ajax_get_datas_disponiveis() {
		check_ajax_referer( 'ae_get_turnos', 'nonce' );

		$metodo = isset( $_POST['metodo'] ) ? sanitize_text_field( wp_unslash( $_POST['metodo'] ) ) : '';

		wp_send_json_success(
			array(
				'datas' => AE_Disponibilidade::datas_disponiveis( $metodo ),
			)
		);
	}

	public function ajax_get_turnos() {
		check_ajax_referer( 'ae_get_turnos', 'nonce' );

		$data   = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';
		$metodo = isset( $_POST['metodo'] ) ? sanitize_text_field( wp_unslash( $_POST['metodo'] ) ) : '';

		if ( empty( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Data inválida.', 'agendar-entregas' ) ) );
		}

		$turnos    = AE_Disponibilidade::turnos_disponiveis( $data, $metodo );
		$resultado = array();

		foreach ( $turnos as $turno ) {
			$resultado[] = array(
				'id'    => $turno->id,
				'label' => sprintf( '%s - %s', $turno->hora_inicio, $turno->hora_fim ),
			);
		}

		wp_send_json_success( array( 'turnos' => $resultado ) );
	}
}
