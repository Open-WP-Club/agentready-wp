<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AgentReady_Admin {

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_post_agentready_scan', [ __CLASS__, 'handle_scan' ] );
		add_action( 'admin_post_agentready_save_endpoint', [ __CLASS__, 'handle_save_endpoint' ] );
		add_action( 'admin_post_agentready_delete_endpoint', [ __CLASS__, 'handle_delete_endpoint' ] );
		add_action( 'admin_post_agentready_save_robots_additions', [ __CLASS__, 'handle_save_robots' ] );
		add_filter( 'robots_txt', [ __CLASS__, 'filter_robots_txt' ], 20, 2 );
	}

	public static function register_menus(): void {
		add_menu_page(
			'Agent Ready',
			'Agent Ready',
			'manage_options',
			'agentready',
			[ __CLASS__, 'page_dashboard' ],
			'dashicons-performance',
			80
		);
		add_submenu_page( 'agentready', 'Dashboard',       'Dashboard',       'manage_options', 'agentready',        [ __CLASS__, 'page_dashboard' ] );
		add_submenu_page( 'agentready', 'robots.txt Rules','robots.txt Rules','manage_options', 'agentready-robots', [ __CLASS__, 'page_robots' ] );
		add_submenu_page( 'agentready', 'MCP Server Card', 'MCP Server Card', 'manage_options', 'agentready-mcp',    [ __CLASS__, 'page_endpoint' ] );
		add_submenu_page( 'agentready', 'Agent Skills',    'Agent Skills',    'manage_options', 'agentready-skills', [ __CLASS__, 'page_endpoint' ] );
		add_submenu_page( 'agentready', 'A2A Agent Card',  'A2A Agent Card',  'manage_options', 'agentready-a2a',    [ __CLASS__, 'page_endpoint' ] );
	}

	public static function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'agentready' ) === false ) return;
		wp_enqueue_style( 'agentready-admin', AGENTREADY_URL . 'assets/css/admin.css', [], AGENTREADY_VERSION );
		wp_enqueue_script( 'agentready-admin', AGENTREADY_URL . 'assets/js/admin.js', [ 'jquery' ], AGENTREADY_VERSION, true );
	}

	// -------------------------------------------------------------------------
	// Page: Dashboard
	// -------------------------------------------------------------------------
	public static function page_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$results = AgentReady_Scanner::run();
		include AGENTREADY_DIR . 'admin/views/dashboard.php';
	}

	// -------------------------------------------------------------------------
	// Page: robots.txt additions
	// -------------------------------------------------------------------------
	public static function page_robots(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$additions  = get_option( 'agentready_robots_additions', '' );
		$robots_url = home_url( '/robots.txt' );
		include AGENTREADY_DIR . 'admin/views/robots.php';
	}

	// -------------------------------------------------------------------------
	// Page: generic .well-known endpoint editor
	// -------------------------------------------------------------------------
	public static function page_endpoint(): void {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$page_map = [
			'agentready-mcp'    => [ 'id' => 'agentready_mcp_server_card', 'label' => 'MCP Server Card', 'path' => '/.well-known/mcp/server-card.json',    'gen' => 'generate_mcp_server_card' ],
			'agentready-skills' => [ 'id' => 'agentready_agent_skills',    'label' => 'Agent Skills',    'path' => '/.well-known/agent-skills/index.json', 'gen' => 'generate_agent_skills' ],
			'agentready-a2a'    => [ 'id' => 'agentready_a2a_card',        'label' => 'A2A Agent Card',  'path' => '/.well-known/agent.json',              'gen' => 'generate_a2a_card' ],
		];

		$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		$ep           = $page_map[ $current_page ] ?? null;
		if ( ! $ep ) { wp_die( 'Unknown page.' ); }

		$endpoint_id    = $ep['id'];
		$endpoint_label = $ep['label'];
		$endpoint_path  = $ep['path'];
		$endpoint_url   = home_url( $ep['path'] );
		$json           = AgentReady_Well_Known::get_endpoint_json( $endpoint_id );

		if ( empty( $json ) ) {
			$generator = [ 'AgentReady_Well_Known', $ep['gen'] ];
			$default   = json_encode( call_user_func( $generator ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		} else {
			$default = $json;
		}

		include AGENTREADY_DIR . 'admin/views/endpoint.php';
	}

	// -------------------------------------------------------------------------
	// Action Handlers
	// -------------------------------------------------------------------------
	public static function handle_scan(): void {
		check_admin_referer( 'agentready_scan' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		AgentReady_Scanner::run( true );
		wp_redirect( admin_url( 'admin.php?page=agentready&scanned=1' ) );
		exit;
	}

	public static function handle_save_endpoint(): void {
		check_admin_referer( 'agentready_endpoint' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id   = sanitize_key( $_POST['endpoint_id'] ?? '' );
		$json = wp_unslash( $_POST['endpoint_json'] ?? '' );
		$ok   = AgentReady_Well_Known::save_endpoint( $id, $json );
		$page_map = [
			'agentready_mcp_server_card' => 'agentready-mcp',
			'agentready_agent_skills'    => 'agentready-skills',
			'agentready_a2a_card'        => 'agentready-a2a',
		];
		$page = $page_map[ $id ] ?? 'agentready';
		wp_redirect( admin_url( 'admin.php?page=' . $page . ( $ok ? '&saved=1' : '&error=invalid_json' ) ) );
		exit;
	}

	public static function handle_delete_endpoint(): void {
		check_admin_referer( 'agentready_endpoint' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$id = sanitize_key( $_POST['endpoint_id'] ?? '' );
		AgentReady_Well_Known::delete_endpoint( $id );
		$page_map = [
			'agentready_mcp_server_card' => 'agentready-mcp',
			'agentready_agent_skills'    => 'agentready-skills',
			'agentready_a2a_card'        => 'agentready-a2a',
		];
		$page = $page_map[ $id ] ?? 'agentready';
		wp_redirect( admin_url( 'admin.php?page=' . $page . '&deleted=1' ) );
		exit;
	}

	public static function handle_save_robots(): void {
		check_admin_referer( 'agentready_robots' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
		$additions = wp_unslash( $_POST['robots_additions'] ?? '' );
		update_option( 'agentready_robots_additions', sanitize_textarea_field( $additions ), false );
		delete_transient( AgentReady_Scanner::CACHE_KEY );
		wp_redirect( admin_url( 'admin.php?page=agentready-robots&saved=1' ) );
		exit;
	}

	public static function filter_robots_txt( string $output, bool $public ): string {
		$additions = get_option( 'agentready_robots_additions', '' );
		if ( $additions ) {
			$output .= "\n\n# Agent Ready additions\n" . $additions;
		}
		return $output;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------
	public static function status_badge( string $status ): string {
		$map = [
			'pass' => [ 'label' => 'Pass',    'class' => 'ar-badge ar-badge--pass' ],
			'fail' => [ 'label' => 'Fail',    'class' => 'ar-badge ar-badge--fail' ],
			'warn' => [ 'label' => 'Warning', 'class' => 'ar-badge ar-badge--warn' ],
			'info' => [ 'label' => 'N/A',     'class' => 'ar-badge ar-badge--info' ],
		];
		$b = $map[ $status ] ?? $map['info'];
		return '<span class="' . esc_attr( $b['class'] ) . '">' . esc_html( $b['label'] ) . '</span>';
	}

	public static function plugin_install_link( array $plugin ): string {
		$url = wp_nonce_url(
			admin_url( 'update.php?action=install-plugin&plugin=' . $plugin['slug'] ),
			'install-plugin_' . $plugin['slug']
		);
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $plugin['name'] ) . '</a>';
	}

	public static function score_label( int $score ): string {
		if ( $score >= 80 ) return 'Ready';
		if ( $score >= 50 ) return 'Improving';
		if ( $score >= 25 ) return 'Needs Work';
		return 'Not Ready';
	}
}
