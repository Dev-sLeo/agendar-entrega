<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AE_Admin_Menu {

	/**
	 * Hook suffixes das páginas do plugin, usados para carregar o Tailwind
	 * somente nelas.
	 */
	private $paginas = array();

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registrar_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_tailwind' ) );
	}

	public function registrar_menu() {
		$this->paginas[] = add_menu_page(
			__( 'Agendar Entregas', 'agendar-entregas' ),
			__( 'Entregas', 'agendar-entregas' ),
			'manage_woocommerce',
			'ae-calendario',
			array( $this, 'render_calendario' ),
			'dashicons-calendar-alt',
			56
		);

		$this->paginas[] = add_submenu_page(
			'ae-calendario',
			__( 'Calendário', 'agendar-entregas' ),
			__( 'Calendário', 'agendar-entregas' ),
			'manage_woocommerce',
			'ae-calendario',
			array( $this, 'render_calendario' )
		);

		$this->paginas[] = add_submenu_page(
			'ae-calendario',
			__( 'Turnos', 'agendar-entregas' ),
			__( 'Turnos', 'agendar-entregas' ),
			'manage_woocommerce',
			'ae-turnos',
			array( $this, 'render_turnos' )
		);

		$this->paginas[] = add_submenu_page(
			'ae-calendario',
			__( 'Dias Bloqueados', 'agendar-entregas' ),
			__( 'Dias Bloqueados', 'agendar-entregas' ),
			'manage_woocommerce',
			'ae-dias-bloqueados',
			array( $this, 'render_dias_bloqueados' )
		);

		$this->paginas[] = add_submenu_page(
			'ae-calendario',
			__( 'Importar Planilha', 'agendar-entregas' ),
			__( 'Importar Planilha', 'agendar-entregas' ),
			'manage_woocommerce',
			'ae-importar',
			array( $this, 'render_importar' )
		);
	}

	/**
	 * Carrega o Tailwind (Play CDN) somente nas telas do plugin, para estilizar
	 * o painel sem interferir no restante do admin do WordPress.
	 */
	public function enqueue_tailwind( $hook ) {
		if ( ! in_array( $hook, $this->paginas, true ) ) {
			return;
		}

		wp_enqueue_script( 'ae-tailwind-cdn', 'https://cdn.tailwindcss.com', array(), null, false );
	}

	public function render_calendario() {
		require AE_PLUGIN_DIR . 'includes/admin/views/calendario.php';
	}

	public function render_turnos() {
		require AE_PLUGIN_DIR . 'includes/admin/views/turnos.php';
	}

	public function render_dias_bloqueados() {
		require AE_PLUGIN_DIR . 'includes/admin/views/dias-bloqueados.php';
	}

	public function render_importar() {
		require AE_PLUGIN_DIR . 'includes/admin/views/importar.php';
	}
}
