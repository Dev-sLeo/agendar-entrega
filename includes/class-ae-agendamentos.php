<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AE_Agendamentos {

	const STATUS_RESERVADO = 'reservado';
	const STATUS_CONFIRMADO = 'confirmado';
	const STATUS_CANCELADO = 'cancelado';

	public static function nome_tabela() {
		global $wpdb;
		return $wpdb->prefix . 'ae_agendamentos';
	}

	public static function criar_tabela() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tabela          = self::nome_tabela();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tabela} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			data_entrega DATE NOT NULL,
			turno_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'reservado',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY data_turno (data_entrega, turno_id, status),
			KEY order_id (order_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Conta agendamentos ativos (reservado ou confirmado) para uma data + turno.
	 */
	public static function contar_ativos( $data, $turno_id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$tabela} WHERE data_entrega = %s AND turno_id = %d AND status IN (%s, %s)",
				$data,
				$turno_id,
				self::STATUS_RESERVADO,
				self::STATUS_CONFIRMADO
			)
		);
	}

	public static function criar( $order_id, $data, $turno_id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		$wpdb->insert(
			$tabela,
			array(
				'order_id'     => $order_id,
				'data_entrega' => $data,
				'turno_id'     => $turno_id,
				'status'       => self::STATUS_RESERVADO,
			),
			array( '%d', '%s', '%d', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function obter_por_pedido( $order_id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$tabela} WHERE order_id = %d ORDER BY id DESC LIMIT 1", $order_id )
		);
	}

	/**
	 * Cria ou substitui o agendamento de um pedido (usado ao agendar um pedido
	 * manual do admin, onde não há checkout envolvido).
	 */
	public static function definir_para_pedido( $order_id, $data, $turno_id, $status ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		$existente = self::obter_por_pedido( $order_id );

		if ( $existente ) {
			return $wpdb->update(
				$tabela,
				array(
					'data_entrega' => $data,
					'turno_id'     => $turno_id,
					'status'       => $status,
				),
				array( 'id' => $existente->id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);
		}

		$wpdb->insert(
			$tabela,
			array(
				'order_id'     => $order_id,
				'data_entrega' => $data,
				'turno_id'     => $turno_id,
				'status'       => $status,
			),
			array( '%d', '%s', '%d', '%s' )
		);

		return $wpdb->insert_id;
	}

	public static function atualizar_status_por_pedido( $order_id, $status ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->update(
			$tabela,
			array( 'status' => $status ),
			array( 'order_id' => $order_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Reservas 'reservado' criadas há mais de $horas sem confirmação de pagamento.
	 */
	public static function listar_reservas_expiradas( $horas ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tabela} WHERE status = %s AND created_at < (NOW() - INTERVAL %d HOUR)",
				self::STATUS_RESERVADO,
				$horas
			)
		);
	}

	/**
	 * Agendamentos ativos entre duas datas, para exibição no calendário admin.
	 */
	public static function listar_periodo( $data_inicio, $data_fim ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tabela} WHERE data_entrega BETWEEN %s AND %s AND status IN (%s, %s) ORDER BY data_entrega ASC",
				$data_inicio,
				$data_fim,
				self::STATUS_RESERVADO,
				self::STATUS_CONFIRMADO
			)
		);
	}

	public static function listar_por_data_turno( $data, $turno_id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tabela} WHERE data_entrega = %s AND turno_id = %d AND status IN (%s, %s) ORDER BY id ASC",
				$data,
				$turno_id,
				self::STATUS_RESERVADO,
				self::STATUS_CONFIRMADO
			)
		);
	}
}
