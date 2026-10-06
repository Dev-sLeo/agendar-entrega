<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tela admin de importação de dias bloqueados via arquivo (CSV/XLSX) ou
 * planilha Google Sheets sincronizada periodicamente.
 */
class AE_Admin_Importar {

	public function __construct() {
		add_action( 'admin_post_ae_importar_planilha_arquivo', array( $this, 'importar_arquivo' ) );
		add_action( 'admin_post_ae_salvar_planilha_google', array( $this, 'salvar_planilha_google' ) );
		add_action( 'admin_post_ae_sincronizar_planilha_google', array( $this, 'sincronizar_planilha_google' ) );
	}

	public function importar_arquivo() {
		check_admin_referer( 'ae_importar_planilha_arquivo' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sem permissão.', 'agendar-entregas' ) );
		}

		if ( empty( $_FILES['arquivo']['name'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ae-importar&ae_erro=arquivo' ) );
			exit;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$extensao = strtolower( pathinfo( sanitize_file_name( $_FILES['arquivo']['name'] ), PATHINFO_EXTENSION ) );

		if ( ! in_array( $extensao, array( 'csv', 'xlsx' ), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ae-importar&ae_erro=formato' ) );
			exit;
		}

		$enviado = wp_handle_upload(
			$_FILES['arquivo'],
			array(
				'test_form' => false,
				'mimes'     => array(
					'csv'  => 'text/csv',
					'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				),
			)
		);

		if ( isset( $enviado['error'] ) ) {
			wp_safe_redirect( add_query_arg( 'ae_erro', rawurlencode( $enviado['error'] ), admin_url( 'admin.php?page=ae-importar' ) ) );
			exit;
		}

		$resultado = AE_Importador_Dias_Bloqueados::importar_arquivo( $enviado['file'], $extensao );

		// Arquivo temporário de upload não precisa ficar na biblioteca de mídia.
		wp_delete_file( $enviado['file'] );

		if ( is_wp_error( $resultado ) ) {
			AE_Logger::erro( 'Importação manual de planilha (arquivo) falhou: ' . $resultado->get_error_message() );
			wp_safe_redirect( add_query_arg( 'ae_erro', rawurlencode( $resultado->get_error_message() ), admin_url( 'admin.php?page=ae-importar' ) ) );
			exit;
		}

		AE_Logger::info(
			sprintf(
				'Importação manual de planilha (arquivo .%s): %d dia(s) importado(s)/atualizado(s), %d ignorado(s).',
				$extensao,
				$resultado['importados'],
				$resultado['ignorados']
			)
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'ae_sucesso'    => '1',
					'ae_importados' => (int) $resultado['importados'],
					'ae_ignorados'  => (int) $resultado['ignorados'],
				),
				admin_url( 'admin.php?page=ae-importar' )
			)
		);
		exit;
	}

	public function salvar_planilha_google() {
		check_admin_referer( 'ae_salvar_planilha_google' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sem permissão.', 'agendar-entregas' ) );
		}

		$url = isset( $_POST['ae_planilha_google_url'] ) ? esc_url_raw( wp_unslash( $_POST['ae_planilha_google_url'] ) ) : '';

		AE_Google_Sheets_Sync::salvar_url( $url );

		wp_safe_redirect( admin_url( 'admin.php?page=ae-importar&ae_sucesso=1' ) );
		exit;
	}

	public function sincronizar_planilha_google() {
		check_admin_referer( 'ae_sincronizar_planilha_google' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sem permissão.', 'agendar-entregas' ) );
		}

		$sync      = new AE_Google_Sheets_Sync();
		$resultado = $sync->sincronizar();

		if ( is_wp_error( $resultado ) ) {
			wp_safe_redirect( add_query_arg( 'ae_erro', rawurlencode( $resultado->get_error_message() ), admin_url( 'admin.php?page=ae-importar' ) ) );
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'ae_sucesso'    => '1',
					'ae_importados' => (int) $resultado['importados'],
					'ae_ignorados'  => (int) $resultado['ignorados'],
				),
				admin_url( 'admin.php?page=ae-importar' )
			)
		);
		exit;
	}
}
