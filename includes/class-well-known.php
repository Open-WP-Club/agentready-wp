<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Serves /.well-known/ endpoints via WordPress rewrite rules.
 * Content is stored in wp_options and returned as JSON.
 */
class AgentReady_Well_Known {

	const ENDPOINTS = [
		'agentready_mcp_server_card' => [
			'path'    => '.well-known/mcp/server-card.json',
			'option'  => 'agentready_mcp_server_card',
			'ct'      => 'application/json',
		],
		'agentready_agent_skills' => [
			'path'    => '.well-known/agent-skills/index.json',
			'option'  => 'agentready_agent_skills',
			'ct'      => 'application/json',
		],
		'agentready_a2a_card' => [
			'path'    => '.well-known/agent.json',
			'option'  => 'agentready_a2a_card',
			'ct'      => 'application/json',
		],
	];

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_rewrites' ] );
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
		add_action( 'template_redirect', [ __CLASS__, 'handle_request' ] );
	}

	public static function register_rewrites(): void {
		foreach ( self::ENDPOINTS as $qvar => $ep ) {
			add_rewrite_rule(
				'^' . preg_quote( $ep['path'], '/' ) . '$',
				'index.php?' . $qvar . '=1',
				'top'
			);
		}
	}

	public static function add_query_vars( array $vars ): array {
		foreach ( self::ENDPOINTS as $qvar => $ep ) {
			$vars[] = $qvar;
		}
		return $vars;
	}

	public static function handle_request(): void {
		foreach ( self::ENDPOINTS as $qvar => $ep ) {
			if ( '1' !== get_query_var( $qvar ) ) {
				continue;
			}
			$content = get_option( $ep['option'], '' );
			if ( empty( $content ) ) {
				status_header( 404 );
				exit( 'Not found' );
			}
			header( 'Content-Type: ' . $ep['ct'] . '; charset=utf-8' );
			header( 'Cache-Control: public, max-age=3600' );
			echo $content;
			exit;
		}
	}

	// -------------------------------------------------------------------------
	// Generators
	// -------------------------------------------------------------------------

	public static function generate_mcp_server_card(): array {
		$site_url  = home_url();
		$site_name = get_bloginfo( 'name' );
		return [
			'schema'     => 'https://modelcontextprotocol.io/schema/server-card/v1',
			'serverInfo' => [
				'name'    => $site_name,
				'version' => '1.0.0',
			],
			'description' => get_bloginfo( 'description' ),
			'url'         => $site_url,
			'transport'   => [
				[ 'type' => 'http', 'url' => $site_url . '/mcp' ],
			],
			'capabilities' => [
				'tools'     => true,
				'resources' => false,
				'prompts'   => false,
			],
		];
	}

	public static function generate_agent_skills(): array {
		$site_name = get_bloginfo( 'name' );
		return [
			'$schema'  => 'https://agentskills.io/schema/v0.2.0/index.json',
			'name'     => $site_name,
			'skills'   => [],
		];
	}

	public static function generate_a2a_card(): array {
		$site_url  = home_url();
		$site_name = get_bloginfo( 'name' );
		return [
			'name'           => $site_name,
			'description'    => get_bloginfo( 'description' ),
			'url'            => $site_url,
			'version'        => '1.0.0',
			'capabilities'   => [
				'streaming'         => false,
				'pushNotifications' => false,
			],
			'defaultInputModes'  => [ 'text' ],
			'defaultOutputModes' => [ 'text' ],
			'skills'             => [],
		];
	}

	public static function save_endpoint( string $id, string $json ): bool {
		if ( ! isset( self::ENDPOINTS[ $id ] ) ) {
			return false;
		}
		// Validate JSON
		json_decode( $json );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return false;
		}
		update_option( self::ENDPOINTS[ $id ]['option'], $json, false );
		delete_transient( AgentReady_Scanner::CACHE_KEY );
		return true;
	}

	public static function get_endpoint_json( string $id ): string {
		if ( ! isset( self::ENDPOINTS[ $id ] ) ) {
			return '';
		}
		return (string) get_option( self::ENDPOINTS[ $id ]['option'], '' );
	}

	public static function delete_endpoint( string $id ): void {
		if ( isset( self::ENDPOINTS[ $id ] ) ) {
			delete_option( self::ENDPOINTS[ $id ]['option'] );
			delete_transient( AgentReady_Scanner::CACHE_KEY );
		}
	}
}
