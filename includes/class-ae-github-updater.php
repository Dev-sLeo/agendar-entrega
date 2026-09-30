<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verificador de atualizações via GitHub Releases, sem depender do
 * WordPress.org nem de bibliotecas externas. Lê a última release publicada
 * no repositório e injeta a informação no fluxo normal de atualização de
 * plugins do WordPress (Painel > Plugins).
 *
 * Fluxo esperado no repositório: criar uma tag/release no GitHub (ex.: v1.2.0)
 * com o valor igual ao "Version" do cabeçalho do plugin (o "v" inicial é
 * opcional, é removido na comparação). O changelog exibido no popup "Ver
 * detalhes" vem da descrição da release.
 */
class AE_GitHub_Updater {

	/**
	 * Repositório no formato "owner/repo".
	 */
	const REPO = 'Dev-sLeo/agendar-entrega';

	const CACHE_KEY = 'ae_github_updater_release';

	/**
	 * Evita bater na API do GitHub a cada carregamento de página admin —
	 * a API pública tem limite de 60 requisições/hora por IP.
	 */
	const CACHE_HORAS = 6;

	public function __construct() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'checar_atualizacao' ) );
		add_filter( 'plugins_api', array( $this, 'detalhes_plugin' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'corrigir_pasta_extraida' ), 10, 4 );
		add_filter( 'upgrader_pre_download', array( $this, 'usar_token_no_download' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'link_changelog' ), 10, 2 );
	}

	private function arquivo_plugin() {
		return plugin_basename( AE_PLUGIN_FILE );
	}

	private function slug_plugin() {
		return dirname( $this->arquivo_plugin() );
	}

	/**
	 * Token de acesso pessoal do GitHub, necessário apenas se o repositório
	 * for privado. Definir via wp-config.php: define('AE_GITHUB_TOKEN', '...').
	 */
	private function token() {
		return defined( 'AE_GITHUB_TOKEN' ) ? AE_GITHUB_TOKEN : '';
	}

	private function cabecalhos_requisicao() {
		$cabecalhos = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'WordPress/AgendarEntregas',
		);

		if ( $this->token() ) {
			$cabecalhos['Authorization'] = 'Bearer ' . $this->token();
		}

		return $cabecalhos;
	}

	/**
	 * Busca a última release publicada no GitHub, com cache curto para não
	 * estourar o limite de requisições da API.
	 */
	private function obter_release() {
		$cache = get_transient( self::CACHE_KEY );
		if ( false !== $cache ) {
			return $cache;
		}

		$resposta = wp_remote_get(
			sprintf( 'https://api.github.com/repos/%s/releases/latest', self::REPO ),
			array(
				'headers' => $this->cabecalhos_requisicao(),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $resposta ) || 200 !== (int) wp_remote_retrieve_response_code( $resposta ) ) {
			// Cacheia a falha também (por menos tempo), para não tentar de novo
			// a cada requisição em caso de rate limit ou repositório fora do ar.
			set_transient( self::CACHE_KEY, array(), 15 * MINUTE_IN_SECONDS );
			return array();
		}

		$dados = json_decode( wp_remote_retrieve_body( $resposta ), true );
		$dados = is_array( $dados ) ? $dados : array();

		set_transient( self::CACHE_KEY, $dados, self::CACHE_HORAS * HOUR_IN_SECONDS );

		return $dados;
	}

	public function checar_atualizacao( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}

		$release = $this->obter_release();
		if ( empty( $release['tag_name'] ) || empty( $release['zipball_url'] ) ) {
			return $transient;
		}

		$versao_remota = ltrim( $release['tag_name'], 'vV' );

		if ( ! version_compare( $versao_remota, AE_VERSION, '>' ) ) {
			return $transient;
		}

		$info = new stdClass();
		$info->slug         = $this->slug_plugin();
		$info->plugin       = $this->arquivo_plugin();
		$info->new_version  = $versao_remota;
		$info->url          = 'https://github.com/' . self::REPO;
		$info->package      = $release['zipball_url'];
		$info->tested       = get_bloginfo( 'version' );
		$info->requires_php = '7.4';

		if ( ! is_array( $transient->response ?? null ) ) {
			$transient->response = array();
		}

		$transient->response[ $this->arquivo_plugin() ] = $info;

		return $transient;
	}

	/**
	 * Popup "Ver detalhes da versão" na lista de plugins.
	 */
	public function detalhes_plugin( $resultado, $acao, $args ) {
		if ( 'plugin_information' !== $acao || empty( $args->slug ) || $this->slug_plugin() !== $args->slug ) {
			return $resultado;
		}

		$release = $this->obter_release();
		if ( empty( $release['tag_name'] ) ) {
			return $resultado;
		}

		$info                 = new stdClass();
		$info->name           = 'Agendar Entregas';
		$info->slug           = $this->slug_plugin();
		$info->version        = ltrim( $release['tag_name'], 'vV' );
		$info->author         = '<a href="https://github.com/' . self::REPO . '">Upsites</a>';
		$info->homepage       = 'https://github.com/' . self::REPO;
		$info->download_link  = $release['zipball_url'];
		$info->requires       = '5.8';
		$info->requires_php   = '7.4';
		$info->last_updated   = isset( $release['published_at'] ) ? $release['published_at'] : '';
		$info->sections       = array(
			'description' => __( 'Agendamento de entregas no checkout do WooCommerce, com turnos, limite de vagas por dia e calendário administrativo.', 'agendar-entregas' ),
			'changelog'   => ! empty( $release['body'] ) ? wpautop( wp_kses_post( $release['body'] ) ) : __( 'Sem notas de versão.', 'agendar-entregas' ),
		);

		return $info;
	}

	/**
	 * O zip baixado do GitHub extrai numa pasta "owner-repo-hash", não
	 * "agendar-entregas" — renomeia para o slug correto, senão o WordPress
	 * cria uma cópia nova desativada em vez de atualizar a existente.
	 */
	public function corrigir_pasta_extraida( $source, $remote_source, $upgrader, $extra = array() ) {
		global $wp_filesystem;

		if ( empty( $extra['plugin'] ) || $this->arquivo_plugin() !== $extra['plugin'] ) {
			return $source;
		}

		$pasta_correta = trailingslashit( $remote_source ) . $this->slug_plugin();

		if ( trailingslashit( $source ) === trailingslashit( $pasta_correta ) ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $pasta_correta, true ) ) {
			return trailingslashit( $pasta_correta );
		}

		return $source;
	}

	/**
	 * Repositórios privados exigem o token também no download do zipball
	 * (a URL da API sozinha não basta).
	 */
	public function usar_token_no_download( $reply, $package, $upgrader ) {
		if ( ! $this->token() || false === strpos( (string) $package, 'api.github.com/repos/' . self::REPO ) ) {
			return $reply;
		}

		add_filter(
			'http_request_args',
			function ( $args ) {
				$args['headers']['Authorization'] = 'Bearer ' . $this->token();
				$args['headers']['Accept']        = 'application/vnd.github+json';
				return $args;
			}
		);

		return $reply;
	}

	public function link_changelog( $links, $arquivo ) {
		if ( $this->arquivo_plugin() === $arquivo ) {
			$links[] = '<a href="https://github.com/' . self::REPO . '/releases" target="_blank">' . esc_html__( 'Ver changelog', 'agendar-entregas' ) . '</a>';
		}

		return $links;
	}
}
