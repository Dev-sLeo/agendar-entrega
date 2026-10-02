<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url_planilha      = AE_Google_Sheets_Sync::url_configurada();
$ultima_sync       = AE_Google_Sheets_Sync::ultima_sincronizacao();
$ultimo_erro       = AE_Google_Sheets_Sync::ultimo_erro();
$ultimo_resumo     = AE_Google_Sheets_Sync::ultimo_resumo();

$input_class = 'block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
$label_class = 'block text-sm font-medium text-gray-700 mb-1';
$botao_class = 'inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500';
?>
<div class="wrap ae-painel">
	<div class="max-w-4xl">
		<h1 class="text-2xl font-semibold text-gray-900 mb-2"><?php esc_html_e( 'Importar Planilha de Dias Bloqueados', 'agendar-entregas' ); ?></h1>
		<p class="text-sm text-gray-500 mb-6">
			<?php esc_html_e( 'Preencha feriados e dias de manutenção em lote, via arquivo ou uma planilha do Google Sheets sincronizada automaticamente. As colunas esperadas são "data" (AAAA-MM-DD ou DD/MM/AAAA) e "motivo".', 'agendar-entregas' ); ?>
		</p>

		<?php if ( isset( $_GET['ae_sucesso'] ) ) : ?>
			<div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
				<?php
				if ( isset( $_GET['ae_importados'] ) ) {
					printf(
						/* translators: 1: quantidade importada, 2: quantidade ignorada */
						esc_html__( '%1$d dia(s) importado(s)/atualizado(s). %2$d linha(s) ignorada(s) por data inválida.', 'agendar-entregas' ),
						(int) $_GET['ae_importados'],
						(int) $_GET['ae_ignorados']
					);
				} else {
					esc_html_e( 'Salvo com sucesso.', 'agendar-entregas' );
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( isset( $_GET['ae_erro'] ) ) : ?>
			<div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
				<?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['ae_erro'] ) ) ); ?>
			</div>
		<?php endif; ?>

		<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
			<h2 class="text-lg font-semibold text-gray-900 mb-1"><?php esc_html_e( 'Enviar arquivo (CSV ou XLSX)', 'agendar-entregas' ); ?></h2>
			<p class="text-xs text-gray-500 mb-4">
				<?php esc_html_e( 'Datas já bloqueadas têm apenas o motivo atualizado; nenhum dia é removido por esta importação.', 'agendar-entregas' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'ae_importar_planilha_arquivo' ); ?>
				<input type="hidden" name="action" value="ae_importar_planilha_arquivo" />

				<input type="file" name="arquivo" accept=".csv,.xlsx" required class="<?php echo esc_attr( $input_class ); ?> mb-4" />

				<button type="submit" class="<?php echo esc_attr( $botao_class ); ?>">
					<?php esc_html_e( 'Importar arquivo', 'agendar-entregas' ); ?>
				</button>
			</form>
		</div>

		<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
			<h2 class="text-lg font-semibold text-gray-900 mb-1"><?php esc_html_e( 'Sincronizar com Google Sheets', 'agendar-entregas' ); ?></h2>
			<p class="text-xs text-gray-500 mb-4">
				<?php
				esc_html_e( 'Cole o link de compartilhamento da planilha (compartilhada como "Qualquer pessoa com o link pode visualizar"). A sincronização automática roda duas vezes ao dia; você também pode sincronizar manualmente a qualquer momento.', 'agendar-entregas' );
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mb-4">
				<?php wp_nonce_field( 'ae_salvar_planilha_google' ); ?>
				<input type="hidden" name="action" value="ae_salvar_planilha_google" />

				<label for="ae_planilha_google_url" class="<?php echo esc_attr( $label_class ); ?>">
					<?php esc_html_e( 'URL da planilha', 'agendar-entregas' ); ?>
				</label>
				<input type="url" name="ae_planilha_google_url" id="ae_planilha_google_url"
					value="<?php echo esc_attr( $url_planilha ); ?>"
					placeholder="https://docs.google.com/spreadsheets/d/..."
					class="<?php echo esc_attr( $input_class ); ?> mb-4" />

				<button type="submit" class="<?php echo esc_attr( $botao_class ); ?>">
					<?php esc_html_e( 'Salvar URL', 'agendar-entregas' ); ?>
				</button>
			</form>

			<?php if ( '' !== $url_planilha ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block mb-4">
					<?php wp_nonce_field( 'ae_sincronizar_planilha_google' ); ?>
					<input type="hidden" name="action" value="ae_sincronizar_planilha_google" />
					<button type="submit" class="inline-flex items-center rounded-md bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
						<?php esc_html_e( 'Sincronizar agora', 'agendar-entregas' ); ?>
					</button>
				</form>
			<?php endif; ?>

			<div class="text-sm text-gray-600 border-t border-gray-100 pt-4 mt-2">
				<p>
					<strong><?php esc_html_e( 'Última sincronização:', 'agendar-entregas' ); ?></strong>
					<?php
					$formato_data_hora = get_option( 'date_format', 'd/m/Y' ) . ' ' . get_option( 'time_format', 'H:i' );
					// mysql2date(), não wp_date()+strtotime(): $ultima_sync vem de
					// current_time('mysql'), já no fuso do site - re-interpretar via
					// strtotime() (que assume UTC) deslocaria o horário exibido.
					echo $ultima_sync ? esc_html( mysql2date( $formato_data_hora, $ultima_sync ) ) : esc_html__( 'Nunca sincronizado.', 'agendar-entregas' );
					?>
				</p>
				<?php if ( $ultimo_erro ) : ?>
					<p class="text-red-700 mt-1"><?php echo esc_html( $ultimo_erro ); ?></p>
				<?php elseif ( is_array( $ultimo_resumo ) ) : ?>
					<p class="mt-1">
						<?php
						printf(
							/* translators: 1: quantidade importada, 2: quantidade ignorada */
							esc_html__( '%1$d dia(s) importado(s)/atualizado(s), %2$d ignorado(s).', 'agendar-entregas' ),
							(int) $ultimo_resumo['importados'],
							(int) $ultimo_resumo['ignorados']
						);
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
