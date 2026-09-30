<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leitor mínimo de .xlsx (sem dependências externas): extrai apenas a
 * primeira planilha como uma matriz de strings. Suficiente para importar uma
 * tabela simples de "data, motivo" — não interpreta fórmulas, estilos ou
 * múltiplas abas.
 */
class AE_Xlsx_Reader {

	/**
	 * @return array|WP_Error Lista de linhas (cada uma um array de células).
	 */
	public static function ler( $caminho_arquivo ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'ae_sem_zip', __( 'O servidor não tem suporte a ZipArchive, necessário para ler arquivos .xlsx. Exporte a planilha como CSV.', 'agendar-entregas' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $caminho_arquivo ) ) {
			return new WP_Error( 'ae_xlsx_invalido', __( 'Não foi possível abrir o arquivo .xlsx.', 'agendar-entregas' ) );
		}

		$strings_compartilhadas = self::ler_shared_strings( $zip );

		$planilha_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
		$zip->close();

		if ( false === $planilha_xml ) {
			return new WP_Error( 'ae_xlsx_sem_planilha', __( 'O arquivo .xlsx não contém uma planilha legível.', 'agendar-entregas' ) );
		}

		return self::extrair_linhas( $planilha_xml, $strings_compartilhadas );
	}

	private static function ler_shared_strings( ZipArchive $zip ) {
		$xml_bruto = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( false === $xml_bruto ) {
			return array();
		}

		$xml     = simplexml_load_string( $xml_bruto );
		$textos  = array();

		if ( false === $xml ) {
			return $textos;
		}

		foreach ( $xml->si as $item ) {
			// <si> pode ter texto simples <t> ou texto "rico" com vários <r><t>.
			if ( isset( $item->t ) ) {
				$textos[] = (string) $item->t;
			} else {
				$partes = array();
				foreach ( $item->r as $run ) {
					$partes[] = (string) $run->t;
				}
				$textos[] = implode( '', $partes );
			}
		}

		return $textos;
	}

	private static function extrair_linhas( $planilha_xml, array $strings_compartilhadas ) {
		$xml = simplexml_load_string( $planilha_xml );
		if ( false === $xml ) {
			return new WP_Error( 'ae_xlsx_xml_invalido', __( 'Não foi possível interpretar o conteúdo do .xlsx.', 'agendar-entregas' ) );
		}

		$linhas = array();

		foreach ( $xml->sheetData->row as $linha_xml ) {
			$celulas = array();

			foreach ( $linha_xml->c as $celula ) {
				$tipo  = (string) $celula['t'];
				$valor = isset( $celula->v ) ? (string) $celula->v : '';

				if ( 's' === $tipo ) {
					// Tipo "shared string": o valor é um índice para sharedStrings.xml.
					$indice  = (int) $valor;
					$celulas[] = isset( $strings_compartilhadas[ $indice ] ) ? $strings_compartilhadas[ $indice ] : '';
				} elseif ( 'inlineStr' === $tipo ) {
					$celulas[] = isset( $celula->is->t ) ? (string) $celula->is->t : '';
				} else {
					$celulas[] = $valor;
				}
			}

			$linhas[] = $celulas;
		}

		return $linhas;
	}
}
