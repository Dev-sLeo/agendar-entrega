<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AE_Dias_Bloqueados {

	public static function nome_tabela() {
		global $wpdb;
		return $wpdb->prefix . 'ae_dias_bloqueados';
	}

	public static function criar_tabela() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tabela          = self::nome_tabela();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tabela} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			data DATE NOT NULL,
			motivo VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY data (data)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public static function esta_bloqueado( $data ) {
		if ( self::esta_dia_semana_bloqueado( $data ) ) {
			return true;
		}

		global $wpdb;
		$tabela = self::nome_tabela();

		$existe = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$tabela} WHERE data = %s", $data )
		);

		return ! empty( $existe );
	}

	/**
	 * Dias da semana bloqueados de forma recorrente (0=domingo ... 6=sábado).
	 * Ex.: bloquear todo domingo, independente da data.
	 */
	public static function dias_semana_bloqueados() {
		return array_map( 'intval', (array) get_option( 'ae_dias_semana_bloqueados', array() ) );
	}

	public static function salvar_dias_semana_bloqueados( $dias_semana ) {
		update_option( 'ae_dias_semana_bloqueados', array_map( 'intval', $dias_semana ) );
	}

	public static function esta_dia_semana_bloqueado( $data ) {
		if ( empty( $data ) ) {
			return false;
		}

		// gmdate(), não wp_date(): $data é uma data "pura" (sem horário); converter
		// a meia-noite UTC dessa data para o fuso do site (ex.: Brasil, UTC-3)
		// fazia cair no dia anterior, deslocando o dia da semana em -1 (ex.: uma
		// segunda calculava como domingo, e o bloqueio de domingo bloqueava
		// segunda-feira em vez de domingo).
		$dia_semana = (int) gmdate( 'w', strtotime( $data ) );

		return in_array( $dia_semana, self::dias_semana_bloqueados(), true );
	}

	public static function listar() {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_results( "SELECT * FROM {$tabela} ORDER BY data ASC" );
	}

	public static function adicionar( $data, $motivo ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->insert(
			$tabela,
			array(
				'data'   => $data,
				'motivo' => $motivo,
			),
			array( '%s', '%s' )
		);
	}

	/**
	 * Insere ou, se a data já existir (UNIQUE KEY), atualiza o motivo. Usado
	 * pela importação de planilha/CSV, que roda periodicamente e não deve
	 * falhar nem duplicar linhas quando uma data já está bloqueada.
	 */
	public static function adicionar_ou_atualizar( $data, $motivo ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$tabela} (data, motivo) VALUES (%s, %s)
				ON DUPLICATE KEY UPDATE motivo = VALUES(motivo)",
				$data,
				$motivo
			)
		);
	}

	public static function remover( $id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->delete( $tabela, array( 'id' => (int) $id ), array( '%d' ) );
	}
}
