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

		// Guarda o turno que o cliente já tinha escolhido ANTES de zerar o
		// <select> - "updated_checkout" dispara de novo a cada recálculo do
		// WooCommerce (digitar CEP, mudar endereço, aplicar cupom...), não só
		// quando a data/turno realmente mudam. Sem isso, qualquer um desses
		// recálculos recarregava a lista e forçava de volta o primeiro turno,
		// descartando a escolha do cliente sem ele perceber - o valor
		// realmente enviado no pedido acabava sendo outro turno, não o que
		// aparecia selecionado quando ele escolheu.
		var turnoEscolhidoAntes = $turno.val();

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

			// Mantém a escolha anterior do cliente se ela ainda existir entre
			// as opções retornadas; só cai no primeiro turno da lista (não
			// confiando no navegador selecionar a primeira <option> sozinho)
			// quando não havia escolha prévia ou ela deixou de ter vaga.
			var aindaDisponivel = response.data.turnos.some( function ( turno ) {
				return String( turno.id ) === String( turnoEscolhidoAntes );
			} );

			$turnoAtual.val( aindaDisponivel ? turnoEscolhidoAntes : response.data.turnos[ 0 ].id );

			// A validação do checkout (que libera o botão "Continuar para
			// pagamento") escuta 'change' no <form>, mas em alguns navegadores/
			// versões do jQuery um 'change' disparado via jQuery não chega de
			// forma confiável em listeners nativos (addEventListener) presos
			// no form - por isso disparamos também um Event nativo de verdade,
			// além do trigger do jQuery (mantido por compatibilidade com outros
			// handlers que possam estar presos via jQuery).
			$turnoAtual.trigger( 'change' );
			$turnoAtual[ 0 ].dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} ).fail( function () {
			var $turnoAtual = $( '#ae_turno' );
			if ( $turnoAtual.length ) {
				resetarTurno( $turnoAtual, aeCheckout.textos.semTurno );
			}
		} );
	}

	/**
	 * Instância Flatpickr atual (compartilhada entre chamadas, não presa a um
	 * nó específico do DOM — ver motivo abaixo).
	 */
	var flatpickrInstancia = null;

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

		if ( ! flatpickrInstancia || flatpickrInstancia.input !== $data[ 0 ] ) {
			// O WooCommerce recria este <input> do zero a cada update_checkout
			// (troca de método de entrega, CEP, etc.) — o Flatpickr, porém,
			// anexa o calendário em <body>, não dentro do input, então a
			// instância antiga nunca é removida junto quando o input velho
			// desaparece do DOM. Sem destruí-la, ela vira um popup "fantasma"
			// que continua no body e pode roubar o clique do calendário atual,
			// fazendo o cliente escolher uma data num calendário que não é
			// mais o visível - a data enviada no formulário fica dessincronizada
			// da que aparece selecionada na tela, e o calendário parece travar.
			if ( flatpickrInstancia ) {
				flatpickrInstancia.destroy();
			}

			flatpickrInstancia = window.flatpickr( $data[ 0 ], {
				// dateFormat continua Y-m-d: é o valor "de verdade" enviado no
				// formulário/AJAX (input original, mantido em sincronia mas
				// escondido pelo altInput). altFormat é só o que o cliente vê,
				// seguindo o formato de data configurado em Ajustes > Geral do
				// WordPress (aeCheckout.formatoData), em vez de sempre AAAA-MM-DD.
				dateFormat: 'Y-m-d',
				altInput: true,
				altFormat: aeCheckout.formatoData,
				locale: window.flatpickr.l10ns && window.flatpickr.l10ns.pt ? 'pt' : undefined,
				minDate: aeCheckout.dataMinima,
				defaultDate: $data.val() || null,
				disableMobile: true,
				onChange: function ( datasSelecionadas, dataTexto, instancia ) {
					carregarTurnos( dataTexto );

					// Mesma garantia do 'change' do turno (ver carregarTurnos):
					// dispara um Event nativo no input original, pra validação
					// do checkout saber imediatamente que a data mudou, mesmo se
					// o disparo interno do Flatpickr não chegar a algum listener.
					instancia.input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
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
			if ( response.success && response.data.datas.length && flatpickrInstancia ) {
				flatpickrInstancia.set( 'enable', response.data.datas );
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
