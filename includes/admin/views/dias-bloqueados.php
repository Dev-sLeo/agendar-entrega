<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dias_bloqueados        = AE_Dias_Bloqueados::listar();
$dias_semana_bloqueados = AE_Dias_Bloqueados::dias_semana_bloqueados();
$limites_excecao        = AE_Limite_Excecao::listar();
$turnos                 = AE_CPT_Turno::listar_turnos();
$turnos_por_id          = array();
foreach ( $turnos as $turno ) {
	$turnos_por_id[ $turno->id ] = $turno;
}
$dias_label             = array(
	1 => __( 'Segunda', 'agendar-entregas' ),
	2 => __( 'Terça', 'agendar-entregas' ),
	3 => __( 'Quarta', 'agendar-entregas' ),
	4 => __( 'Quinta', 'agendar-entregas' ),
	5 => __( 'Sexta', 'agendar-entregas' ),
	6 => __( 'Sábado', 'agendar-entregas' ),
	0 => __( 'Domingo', 'agendar-entregas' ),
);

$input_class = 'block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
$label_class = 'block text-sm font-medium text-gray-700 mb-1';
$botao_class = 'inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500';
?>
<div class="wrap ae-painel">
	<div class="max-w-4xl">
		<h1 class="text-2xl font-semibold text-gray-900 mb-2"><?php esc_html_e( 'Dias Bloqueados', 'agendar-entregas' ); ?></h1>
		<p class="text-sm text-gray-500 mb-6">
			<?php esc_html_e( 'Feriados ou dias de manutenção em que nenhuma entrega deve ser agendada, mesmo que haja turnos configurados.', 'agendar-entregas' ); ?>
		</p>

		<?php if ( isset( $_GET['ae_sucesso'] ) ) : ?>
			<div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
				<?php esc_html_e( 'Salvo com sucesso.', 'agendar-entregas' ); ?>
			</div>
		<?php endif; ?>

		<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
			<h2 class="text-lg font-semibold text-gray-900 mb-1"><?php esc_html_e( 'Bloquear dia da semana', 'agendar-entregas' ); ?></h2>
			<p class="text-xs text-gray-500 mb-4">
				<?php esc_html_e( 'Marque os dias da semana em que nenhuma entrega deve ser agendada, todo santo dia (ex.: bloquear domingos).', 'agendar-entregas' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ae_salvar_dias_semana_bloqueados' ); ?>
				<input type="hidden" name="action" value="ae_salvar_dias_semana_bloqueados" />

				<div class="flex flex-wrap gap-4 mb-4">
					<?php foreach ( $dias_label as $valor => $label ) : ?>
						<label class="inline-flex items-center gap-2 text-sm text-gray-700">
							<input type="checkbox" name="dias_semana_bloqueados[]" value="<?php echo esc_attr( $valor ); ?>"
								<?php checked( in_array( $valor, $dias_semana_bloqueados, true ) ); ?>
								class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</div>

				<button type="submit" class="<?php echo esc_attr( $botao_class ); ?>">
					<?php esc_html_e( 'Salvar dias da semana', 'agendar-entregas' ); ?>
				</button>
			</form>
		</div>

		<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
			<h2 class="text-lg font-semibold text-gray-900 mb-1"><?php esc_html_e( 'Bloquear uma data específica', 'agendar-entregas' ); ?></h2>
			<p class="text-xs text-gray-500 mb-4">
				<?php esc_html_e( 'Para feriados ou dias avulsos de manutenção.', 'agendar-entregas' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'ae_salvar_dia_bloqueado' ); ?>
				<input type="hidden" name="action" value="ae_salvar_dia_bloqueado" />

				<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
					<div>
						<label for="data" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Data', 'agendar-entregas' ); ?></label>
						<input type="date" name="data" id="data" required class="<?php echo esc_attr( $input_class ); ?>" />
					</div>
					<div>
						<label for="motivo" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Motivo', 'agendar-entregas' ); ?></label>
						<input type="text" name="motivo" id="motivo"
							placeholder="<?php esc_attr_e( 'Ex: feriado, manutenção', 'agendar-entregas' ); ?>"
							class="<?php echo esc_attr( $input_class ); ?>" />
					</div>
				</div>

				<button type="submit" class="<?php echo esc_attr( $botao_class ); ?>">
					<?php esc_html_e( 'Bloquear dia', 'agendar-entregas' ); ?>
				</button>
			</form>
		</div>

		<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden mb-8">
			<table class="min-w-full divide-y divide-gray-200 text-sm">
				<thead class="bg-gray-50">
					<tr>
						<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Data', 'agendar-entregas' ); ?></th>
						<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Motivo', 'agendar-entregas' ); ?></th>
						<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Ações', 'agendar-entregas' ); ?></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<?php if ( empty( $dias_bloqueados ) ) : ?>
						<tr><td colspan="3" class="px-6 py-4 text-gray-500"><?php esc_html_e( 'Nenhum dia bloqueado.', 'agendar-entregas' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $dias_bloqueados as $dia ) : ?>
						<tr>
							<?php /* mysql2date(), não wp_date()+strtotime(): $dia->data é uma data "pura" (AAAA-MM-DD, sem horário); wp_date()
							converteria para o fuso do site, deslocando a data em -1 em fusos negativos como o do Brasil (ex.: dia 10 virava 09). */ ?>
							<td class="px-6 py-3 text-gray-900"><?php echo esc_html( mysql2date( get_option( 'date_format', 'd/m/Y' ), $dia->data ) ); ?></td>
							<td class="px-6 py-3 text-gray-700"><?php echo esc_html( $dia->motivo ); ?></td>
							<td class="px-6 py-3">
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ae_excluir_dia_bloqueado&id=' . $dia->id ), 'ae_excluir_dia_bloqueado' ) ); ?>"
									onclick="return confirm('<?php echo esc_js( __( 'Remover este bloqueio?', 'agendar-entregas' ) ); ?>');"
									class="text-red-600 hover:text-red-800 font-medium">
									<?php esc_html_e( 'Remover', 'agendar-entregas' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( ! empty( $turnos ) ) : ?>
			<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
				<h2 class="text-lg font-semibold text-gray-900 mb-1"><?php esc_html_e( 'Ajustar limite de vagas para um dia específico', 'agendar-entregas' ); ?></h2>
				<p class="text-xs text-gray-500 mb-4">
					<?php esc_html_e( 'Reduz (ou aumenta) manualmente o limite de um turno só numa data, sem alterar o limite padrão dele. Ex.: turno com limite de 20, mas só 1 vaga disponível na quarta dia 30.', 'agendar-entregas' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'ae_salvar_limite_excecao' ); ?>
					<input type="hidden" name="action" value="ae_salvar_limite_excecao" />

					<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
						<div>
							<label for="limite_data" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Data', 'agendar-entregas' ); ?></label>
							<input type="date" name="data" id="limite_data" required class="<?php echo esc_attr( $input_class ); ?>" />
						</div>
						<div>
							<label for="limite_turno" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Turno', 'agendar-entregas' ); ?></label>
							<select name="turno_id" id="limite_turno" required class="<?php echo esc_attr( $input_class ); ?>">
								<option value=""><?php esc_html_e( 'Selecione…', 'agendar-entregas' ); ?></option>
								<?php foreach ( $turnos as $turno ) : ?>
									<option value="<?php echo esc_attr( $turno->id ); ?>">
										<?php echo esc_html( $turno->nome . ' (padrão: ' . $turno->limite_por_dia . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label for="limite_valor" class="<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Novo limite', 'agendar-entregas' ); ?></label>
							<input type="number" name="limite" id="limite_valor" min="0" step="1" required class="<?php echo esc_attr( $input_class ); ?>" />
						</div>
					</div>

					<button type="submit" class="<?php echo esc_attr( $botao_class ); ?>">
						<?php esc_html_e( 'Salvar ajuste', 'agendar-entregas' ); ?>
					</button>
				</form>
			</div>

			<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
				<table class="min-w-full divide-y divide-gray-200 text-sm">
					<thead class="bg-gray-50">
						<tr>
							<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Data', 'agendar-entregas' ); ?></th>
							<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Turno', 'agendar-entregas' ); ?></th>
							<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Limite ajustado', 'agendar-entregas' ); ?></th>
							<th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wide"><?php esc_html_e( 'Ações', 'agendar-entregas' ); ?></th>
						</tr>
					</thead>
					<tbody class="divide-y divide-gray-100">
						<?php if ( empty( $limites_excecao ) ) : ?>
							<tr><td colspan="4" class="px-6 py-4 text-gray-500"><?php esc_html_e( 'Nenhum ajuste cadastrado.', 'agendar-entregas' ); ?></td></tr>
						<?php endif; ?>
						<?php foreach ( $limites_excecao as $ajuste ) : ?>
							<tr>
								<td class="px-6 py-3 text-gray-900"><?php echo esc_html( mysql2date( get_option( 'date_format', 'd/m/Y' ), $ajuste->data ) ); ?></td>
								<td class="px-6 py-3 text-gray-700">
									<?php echo esc_html( isset( $turnos_por_id[ $ajuste->turno_id ] ) ? $turnos_por_id[ $ajuste->turno_id ]->nome : __( 'Turno removido', 'agendar-entregas' ) ); ?>
								</td>
								<td class="px-6 py-3 text-gray-700"><?php echo esc_html( $ajuste->limite ); ?></td>
								<td class="px-6 py-3">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ae_excluir_limite_excecao&id=' . $ajuste->id ), 'ae_excluir_limite_excecao' ) ); ?>"
										onclick="return confirm('<?php echo esc_js( __( 'Remover este ajuste?', 'agendar-entregas' ) ); ?>');"
										class="text-red-600 hover:text-red-800 font-medium">
										<?php esc_html_e( 'Remover', 'agendar-entregas' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>
