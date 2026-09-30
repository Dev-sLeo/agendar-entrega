jQuery( function ( $ ) {
	'use strict';

	var body = $( document.body );

	/**
	 * Um método de entrega está selecionado quando existe um input
	 * shipping_method marcado (radio) ou um único método já vem como
	 * hidden field (caso de método único, que o WooCommerce não exibe
	 * como opção de escolha).
	 */
	function metodoEntregaSelecionado() {
		var valor = '';

		$( 'input[name^="shipping_method"]' ).each( function () {
			if ( ( 'radio' !== this.type || this.checked ) && $( this ).val() ) {
				valor = $( this ).val();
			}
		} );

		return valor;
	}

	function resetarTurno( $turno, mensagem ) {
		$turno.empty().append( $( '<option>', { value: '', text: mensagem } ) );
	}

	function carregarTurnos( data ) {
		var $data  = $( '#ae_data_entrega' );
		var $turno = $( '#ae_turno' );

		if ( ! $data.length || ! $turno.length ) {
			return;
		}

		if ( ! data ) {
			resetarTurno( $turno, aeCheckout.textos.selecioneData );
			return;
		}

		resetarTurno( $turno, '...' );

		$.post( aeCheckout.ajaxUrl, {
			action: 'ae_get_turnos',
			nonce: aeCheckout.nonce,
			data: data,
			metodo: metodoEntregaSelecionado()
		} ).done( function ( response ) {
			// A tabela de revisão do pedido pode ter sido recarregada via AJAX
			// (update_checkout) enquanto esta requisição estava em andamento.
			var $turnoAtual = $( '#ae_turno' );
			if ( ! $turnoAtual.length ) {
				return;
			}

			if ( ! response.success || ! response.data.turnos.length ) {
				resetarTurno( $turnoAtual, aeCheckout.textos.semTurno );
				return;
			}

			$turnoAtual.empty();
			$.each( response.data.turnos, function ( _, turno ) {
				$turnoAtual.append( $( '<option>', { value: turno.id, text: turno.label } ) );
			} );

			// O navegador seleciona a primeira opção automaticamente, mas isso não
			// dispara 'change' — sem esse evento, a validação do checkout (que
			// libera o botão "Continuar para pagamento") nunca fica sabendo que o
			// campo, antes vazio/inválido, agora já tem um turno válido escolhido.
			$turnoAtual.trigger( 'change' );
		} ).fail( function () {
			var $turnoAtual = $( '#ae_turno' );
			if ( $turnoAtual.length ) {
				resetarTurno( $turnoAtual, aeCheckout.textos.semTurno );
			}
		} );
	}

	/**
	 * Cria (ou reaproveita) o datepicker do campo de data, desabilitando
	 * visualmente os dias sem vaga — o input nativo type="date" não permite
	 * isso, por isso o campo é renderizado como texto (readonly) e o
	 * Flatpickr assume a interface de calendário.
	 */
	function configurarDatepicker( $data ) {
		if ( ! $data.length || 'function' !== typeof window.flatpickr ) {
			return;
		}

		var instancia = $data[0]._flatpickr;

		if ( ! instancia ) {
			instancia = window.flatpickr( $data[0], {
				dateFormat: 'Y-m-d',
				locale: window.flatpickr.l10ns && window.flatpickr.l10ns.pt ? 'pt' : undefined,
				minDate: aeCheckout.dataMinima,
				defaultDate: $data.val() || null,
				disableMobile: true,
				onChange: function ( datasSelecionadas, dataTexto ) {
					carregarTurnos( dataTexto );
				}
			} );
		}

		// Busca as datas com vaga para o método de entrega atual e restringe o
		// calendário a elas (lista branca: mais simples e confiável do que
		// tentar prever regras de bloqueio/lotação em JS).
		$.post( aeCheckout.ajaxUrl, {
			action: 'ae_get_datas_disponiveis',
			nonce: aeCheckout.nonce,
			metodo: metodoEntregaSelecionado()
		} ).done( function ( response ) {
			if ( response.success && response.data.datas.length ) {
				instancia.set( 'enable', response.data.datas );
			}
		} );
	}

	/**
	 * Mostra/esconde a linha de agendamento conforme a escolha do método de
	 * entrega, e mantém os campos coerentes com essa visibilidade: quando
	 * escondidos, viram opcionais para não travar o envio do formulário.
	 */
	function atualizarVisibilidade() {
		var $linha = $( '.ae-linha-agendamento' );
		if ( ! $linha.length ) {
			return;
		}

		var $data  = $linha.find( '#ae_data_entrega' );
		var $turno = $linha.find( '#ae_turno' );

		if ( metodoEntregaSelecionado() ) {
			$linha.show();
			$data.prop( 'required', true );
			$turno.prop( 'required', true );
			configurarDatepicker( $data );
			carregarTurnos( $data.val() );
		} else {
			$linha.hide();
			$data.prop( 'required', false );
			$turno.prop( 'required', false );
		}
	}

	// O WooCommerce substitui a tabela de revisão do pedido (onde nossos campos
	// vivem) via AJAX sempre que o método de entrega ou dados do checkout mudam.
	// Por isso os eventos são delegados em document.body em vez de presos aos
	// elementos originais, que deixam de existir após cada substituição.
	body.on( 'change', 'input[name^="shipping_method"]', atualizarVisibilidade );

	// Cada 'updated_checkout' recria os campos com o placeholder padrão, então
	// a visibilidade, o datepicker e as opções de turno precisam ser
	// reavaliados sempre.
	body.on( 'updated_checkout', atualizarVisibilidade );

	atualizarVisibilidade();
} );
