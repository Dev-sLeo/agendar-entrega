<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importa uma lista de dias bloqueados (feriados, manutenção) a partir de um
 * arquivo CSV/XLSX ou de uma planilha Google Sheets publicada como CSV.
 *
 * Formato esperado: duas colunas, "data" e "motivo" (com ou sem cabeçalho).
 * A data aceita os formatos AAAA-MM-DD, DD/MM/AAAA ou serial numérico do
 * Excel. Datas já bloqueadas manualmente têm o motivo atualizado, nunca são
 * duplicadas (chave única na coluna `data`).
 */
class AE_Importador_Dias_Bloqueados {

	/**
	 * @param string $caminho_arquivo Caminho local do arquivo enviado.
	 * @param string $extensao        'csv' ou 'xlsx'.
	 * @return array|WP_Error Resumo da importação: array( 'total', 'importados', 'ignorados' ).
	 */
	public static function importar_arquivo( $caminho_arquivo, $extensao ) {
		$extensao = strtolower( $extensao );

		if ( 'xlsx' === $extensao ) {
			$linhas = AE_Xlsx_Reader::ler( $caminho_arquivo );
		} elseif ( 'csv' === $extensao ) {
			$linhas = self::ler_csv( $caminho_arquivo );
		} else {
			return new WP_Error( 'ae_formato_invalido', __( 'Formato de arquivo não suportado. Envie um .csv ou .xlsx.', 'agendar-entregas' ) );
		}

		if ( is_wp_error( $linhas ) ) {
			return $linhas;
		}

		return self::processar_linhas( $linhas );
	}

	/**
	 * @param string $conteudo_csv Conteúdo bruto de um CSV (ex.: baixado de uma URL).
	 */
	public static function importar_conteudo_csv( $conteudo_csv ) {
		$linhas_brutas = preg_split( "/\r\n|\n|\r/", trim( $conteudo_csv ) );
		$linhas        = array_map( 'str_getcsv', array_filter( $linhas_brutas, 'strlen' ) );

		return self::processar_linhas( $linhas );
	}

	private static function ler_csv( $caminho_arquivo ) {
		$handle = fopen( $caminho_arquivo, 'r' );
		if ( false === $handle ) {
			return new WP_Error( 'ae_csv_ilegivel', __( 'Não foi possível ler o arquivo CSV.', 'agendar-entregas' ) );
		}

		$linhas = array();
		while ( false !== ( $linha = fgetcsv( $handle ) ) ) {
			$linhas[] = $linha;
		}
		fclose( $handle );

		return $linhas;
	}

	private static function processar_linhas( array $linhas ) {
		$total      = 0;
		$importados = 0;
		$ignorados  = 0;

		foreach ( $linhas as $linha ) {
			if ( empty( $linha ) || ! isset( $linha[0] ) ) {
				continue;
			}

			$bruto_data = trim( (string) $linha[0] );
			$motivo     = isset( $linha[1] ) ? trim( (string) $linha[1] ) : '';

			// Ignora a linha de cabeçalho (ex.: "data,motivo").
			if ( 0 === strcasecmp( $bruto_data, 'data' ) ) {
				continue;
			}

			if ( '' === $bruto_data ) {
				continue;
			}

			$total++;
			$data_normalizada = self::normalizar_data( $bruto_data );

			if ( ! $data_normalizada ) {
				$ignorados++;
				continue;
			}

			AE_Dias_Bloqueados::adicionar_ou_atualizar( $data_normalizada, $motivo );
			$importados++;
		}

		return array(
			'total'      => $total,
			'importados' => $importados,
			'ignorados'  => $ignorados,
		);
	}

	/**
	 * Aceita AAAA-MM-DD, DD/MM/AAAA e serial numérico de data do Excel/Sheets,
	 * retornando sempre no formato AAAA-MM-DD ou false se não reconhecido.
	 */
	private static function normalizar_data( $valor ) {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valor ) ) {
			return $valor;
		}

		if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $partes ) ) {
			return sprintf( '%04d-%02d-%02d', $partes[3], $partes[2], $partes[1] );
		}

		// Serial de data do Excel: dias desde 1899-12-30 (epoch do Excel).
		if ( is_numeric( $valor ) && $valor > 0 ) {
			$timestamp = ( (int) $valor - 25569 ) * DAY_IN_SECONDS;
			return gmdate( 'Y-m-d', $timestamp );
		}

		$timestamp = strtotime( $valor );
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : false;
	}
}
