<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajuste manual do limite de vagas de um turno em uma data específica,
 * sobrepondo o limite_por_dia padrão do turno. Ex.: turno com limite normal
 * de 20, mas só 1 vaga disponível na quarta-feira dia 30.
 */
class AE_Limite_Excecao {

	public static function nome_tabela() {
		global $wpdb;
		return $wpdb->prefix . 'ae_limites_excecao';
	}

	public static function criar_tabela() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tabela          = self::nome_tabela();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$tabela} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			data DATE NOT NULL,
			turno_id BIGINT UNSIGNED NOT NULL,
			limite INT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY data_turno (data, turno_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * @return int|null Limite ajustado para a data+turno, ou null se não houver.
	 */
	public static function obter( $data, $turno_id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		$limite = $wpdb->get_var(
			$wpdb->prepare( "SELECT limite FROM {$tabela} WHERE data = %s AND turno_id = %d", $data, $turno_id )
		);

		return null !== $limite ? (int) $limite : null;
	}

	/**
	 * Cria ou substitui o ajuste de limite para a data+turno.
	 */
	public static function definir( $data, $turno_id, $limite ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->replace(
			$tabela,
			array(
				'data'     => $data,
				'turno_id' => $turno_id,
				'limite'   => $limite,
			),
			array( '%s', '%d', '%d' )
		);
	}

	public static function remover( $id ) {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->delete( $tabela, array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Lista todos os ajustes cadastrados, mais recentes por data primeiro.
	 */
	public static function listar() {
		global $wpdb;
		$tabela = self::nome_tabela();

		return $wpdb->get_results( "SELECT * FROM {$tabela} ORDER BY data ASC" );
	}
}
