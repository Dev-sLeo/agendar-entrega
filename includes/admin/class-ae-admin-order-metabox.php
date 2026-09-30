<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permite agendar a entrega diretamente na tela de edição do pedido, para
 * pedidos criados manualmente no admin (sem passar pelo checkout).
 */
class AE_Admin_Order_Metabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'registrar_metabox' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'salvar' ) );
	}

	/**
	 * Funciona tanto com pedidos como CPT quanto com o armazenamento em
	 * tabelas próprias do WooCommerce (HPOS): a tela de edição usa uma "screen
	 * id" diferente em cada caso.
	 */
	private function tela_pedido() {
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			return wc_get_page_screen_id( 'shop-order' );
		}

		return 'shop_order';
	}

	public function registrar_metabox() {
		add_meta_box(
			'ae_agendamento_pedido',
			__( 'Agendamento de Entrega', 'agendar-entregas' ),
			array( $this, 'render' ),
			$this->tela_pedido(),
			'side',
			'default'
		);
	}

	public function render( $post_ou_pedido ) {
		$order = ( $post_ou_pedido instanceof WP_Post ) ? wc_get_order( $post_ou_pedido->ID ) : wc_get_order( $post_ou_pedido );
		if ( ! $order ) {
			return;
		}

		$data_atual  = $order->get_meta( '_ae_data_entrega', true );
		$turno_atual = (int) $order->get_meta( '_ae_turno_id', true );

		wp_nonce_field( 'ae_salvar_agendamento_pedido', 'ae_agendamento_nonce' );
		?>
		<p>
			<label for="ae_data_entrega_admin"><strong><?php esc_html_e( 'Data de entrega', 'agendar-entregas' ); ?></strong></label><br />
			<input type="date" style="width:100%;" id="ae_data_entrega_admin" name="ae_data_entrega_admin"
				value="<?php echo esc_attr( $data_atual ); ?>" />
		</p>
		<p>
			<label for="ae_turno_admin"><strong><?php esc_html_e( 'Turno', 'agendar-entregas' ); ?></strong></label><br />
			<select id="ae_turno_admin" name="ae_turno_admin" style="width:100%;">
				<option value=""><?php esc_html_e( '— Nenhum —', 'agendar-entregas' ); ?></option>
				<?php foreach ( AE_CPT_Turno::listar_turnos() as $turno ) : ?>
					<option value="<?php echo esc_attr( $turno->id ); ?>" <?php selected( $turno_atual, $turno->id ); ?>>
						<?php echo esc_html( $turno->nome . ' (' . $turno->hora_inicio . ' - ' . $turno->hora_fim . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="description">
			<?php esc_html_e( 'Agendamento manual: não valida disponibilidade de vagas, útil para pedidos criados diretamente no admin.', 'agendar-entregas' ); ?>
		</p>
		<?php
	}

	public function salvar( $order_id ) {
		if ( ! isset( $_POST['ae_agendamento_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ae_agendamento_nonce'] ) ), 'ae_salvar_agendamento_pedido' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$data     = isset( $_POST['ae_data_entrega_admin'] ) ? sanitize_text_field( wp_unslash( $_POST['ae_data_entrega_admin'] ) ) : '';
		$turno_id = isset( $_POST['ae_turno_admin'] ) ? absint( $_POST['ae_turno_admin'] ) : 0;

		if ( empty( $data ) || empty( $turno_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$order->update_meta_data( '_ae_data_entrega', $data );
		$order->update_meta_data( '_ae_turno_id', $turno_id );
		$order->save();

		$status = $order->has_status( array( 'processing', 'completed' ) )
			? AE_Agendamentos::STATUS_CONFIRMADO
			: AE_Agendamentos::STATUS_RESERVADO;

		AE_Agendamentos::definir_para_pedido( $order_id, $data, $turno_id, $status );
	}
}
