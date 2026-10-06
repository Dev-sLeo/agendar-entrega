<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper fino sobre o logger nativo do WooCommerce, para que os eventos do
 * plugin apareçam em WooCommerce > Status > Logs (em vez de só no
 * error_log do servidor, que o admin da loja normalmente não tem acesso).
 * O arquivo de log fica listado lá com a fonte "agendar-entregas".
 */
class AE_Logger {

	const FONTE = 'agendar-entregas';

	private static function logger() {
		return function_exists( 'wc_get_logger' ) ? wc_get_logger() : null;
	}

	private static function contexto() {
		return array( 'source' => self::FONTE );
	}

	public static function info( $mensagem ) {
		$logger = self::logger();
		if ( $logger ) {
			$logger->info( $mensagem, self::contexto() );
		}
	}

	public static function aviso( $mensagem ) {
		$logger = self::logger();
		if ( $logger ) {
			$logger->warning( $mensagem, self::contexto() );
		}
	}

	public static function erro( $mensagem ) {
		$logger = self::logger();
		if ( $logger ) {
			$logger->error( $mensagem, self::contexto() );
		}
	}
}
