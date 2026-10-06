<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincroniza periodicamente uma planilha Google Sheets publicada como CSV,
 * preenchendo/atualizando os dias bloqueados. Usa o link de exportação CSV
 * público do Sheets (não requer OAuth nem credenciais de API do Google).
 */
class AE_Google_Sheets_Sync {

	const CRON_HOOK = 'ae_sincronizar_planilha_google';

	public function __construct() {
		add_action( self::CRON_HOOK, array( $this, 'sincronizar' ) );
	}

	public static function url_configurada() {
		return trim( (string) get_option( 'ae_planilha_google_url', '' ) );
	}

	public static function salvar_url( $url ) {
		update_option( 'ae_planilha_google_url', $url );
	}

	/**
	 * Aceita tanto o link de edição normal (.../edit#gid=0) quanto um link já
	 * de exportação, convertendo sempre para a URL de exportação CSV da aba.
	 */
	public static function url_exportacao_csv( $url ) {
		if ( '' === $url ) {
			return '';
		}

		if ( false !== strpos( $url, 'export?format=csv' ) || false !== strpos( $url, 'output=csv' ) ) {
			return $url;
		}

		if ( ! preg_match( '#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $partes ) ) {
			return '';
		}

		$id  = $partes[1];
		$gid = '0';
		if ( preg_match( '/[#&]gid=(\d+)/', $url, $partes_gid ) ) {
			$gid = $partes_gid[1];
		}

		return sprintf( 'https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s', $id, $gid );
	}

	/**
	 * @return array|WP_Error Resumo da importação, igual ao do importador de arquivo.
	 */
	public function sincronizar() {
		$url = self::url_configurada();

		if ( '' === $url ) {
			return new WP_Error( 'ae_sem_url', __( 'Nenhuma planilha configurada.', 'agendar-entregas' ) );
		}

		$url_csv = self::url_exportacao_csv( $url );
		if ( '' === $url_csv ) {
			$resultado = new WP_Error( 'ae_url_invalida', __( 'URL da planilha inválida. Use o link de compartilhamento do Google Sheets.', 'agendar-entregas' ) );
			self::registrar_resultado( $resultado );
			return $resultado;
		}

		$resposta = wp_remote_get( $url_csv, array( 'timeout' => 20 ) );

		if ( is_wp_error( $resposta ) ) {
			self::registrar_resultado( $resposta );
			return $resposta;
		}

		$codigo = wp_remote_retrieve_response_code( $resposta );
		if ( 200 !== (int) $codigo ) {
			$resultado = new WP_Error(
				'ae_http_' . $codigo,
				/* translators: %d: código HTTP retornado pelo Google Sheets */
				sprintf( __( 'A planilha respondeu com erro (HTTP %d). Verifique se o link está publicado/compartilhado como "Qualquer pessoa com o link".', 'agendar-entregas' ), $codigo )
			);
			self::registrar_resultado( $resultado );
			return $resultado;
		}

		$resultado = AE_Importador_Dias_Bloqueados::importar_conteudo_csv( wp_remote_retrieve_body( $resposta ) );
		self::registrar_resultado( $resultado );

		return $resultado;
	}

	private static function registrar_resultado( $resultado ) {
		update_option( 'ae_planilha_ultima_sincronizacao', current_time( 'mysql' ) );

		if ( is_wp_error( $resultado ) ) {
			update_option( 'ae_planilha_ultimo_erro', $resultado->get_error_message() );
			AE_Logger::erro( 'Sincronização da planilha Google Sheets falhou: ' . $resultado->get_error_message() );
			return;
		}

		delete_option( 'ae_planilha_ultimo_erro' );
		update_option( 'ae_planilha_ultimo_resumo', $resultado );
		AE_Logger::info(
			sprintf(
				'Sincronização da planilha Google Sheets concluída: %d dia(s) importado(s)/atualizado(s), %d ignorado(s).',
				$resultado['importados'],
				$resultado['ignorados']
			)
		);
	}

	public static function ultima_sincronizacao() {
		return get_option( 'ae_planilha_ultima_sincronizacao', '' );
	}

	public static function ultimo_erro() {
		return get_option( 'ae_planilha_ultimo_erro', '' );
	}

	public static function ultimo_resumo() {
		return get_option( 'ae_planilha_ultimo_resumo', null );
	}
}
