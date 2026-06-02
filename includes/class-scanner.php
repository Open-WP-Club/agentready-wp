<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AgentReady_Scanner {

	const CACHE_KEY     = 'agentready_scan_results';
	const CACHE_SECONDS = 3600;

	// Known AI crawler user-agents to look for in robots.txt
	const AI_BOTS = [
		'GPTBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-Web', 'anthropic-ai',
		'PerplexityBot', 'Googlebot-Extended', 'Google-Extended', 'Applebot-Extended',
		'CCBot', 'Omgilibot', 'YouBot', 'Bytespider', 'cohere-ai',
	];

	public static function run( bool $force = false ): array {
		if ( ! $force ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$base = trailingslashit( home_url() );
		$results = [
			'scanned_at' => time(),
			'categories' => [],
		];

		$results['categories']['discoverability']      = self::check_discoverability( $base );
		$results['categories']['content']              = self::check_content( $base );
		$results['categories']['bot_access']           = self::check_bot_access( $base );
		$results['categories']['protocol_discovery']   = self::check_protocol_discovery( $base );
		$results['categories']['seo_basics']           = self::check_seo_basics( $base );
		$results['categories']['commerce']             = self::check_commerce( $base );

		// Compute overall score
		$total = 0; $passed = 0;
		foreach ( $results['categories'] as $cat ) {
			foreach ( $cat['checks'] as $check ) {
				if ( $check['weight'] === 0 ) continue;
				$total  += $check['weight'];
				if ( $check['status'] === 'pass' ) {
					$passed += $check['weight'];
				}
			}
		}
		$results['score'] = $total > 0 ? (int) round( ( $passed / $total ) * 100 ) : 0;

		set_transient( self::CACHE_KEY, $results, self::CACHE_SECONDS );
		return $results;
	}

	// -------------------------------------------------------------------------
	// Discoverability
	// -------------------------------------------------------------------------
	private static function check_discoverability( string $base ): array {
		return [
			'label'  => 'Discoverability',
			'checks' => [
				self::check_robots_txt( $base ),
				self::check_sitemap( $base ),
				self::check_llms_txt( $base ),
				self::check_link_headers( $base ),
			],
		];
	}

	private static function check_robots_txt( string $base ): array {
		$url      = $base . 'robots.txt';
		$response = wp_remote_get( $url, [ 'timeout' => 8, 'sslverify' => false ] );
		$found    = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		return [
			'id'          => 'robots_txt',
			'label'       => 'robots.txt',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 5,
			'description' => $found
				? 'A valid robots.txt file was found.'
				: 'No robots.txt file found.',
			'fix'         => $found ? null : 'WordPress generates a basic robots.txt automatically. Make sure no plugin is blocking it, or create one manually.',
			'fix_type'    => 'info',
		];
	}

	private static function check_sitemap( string $base ): array {
		// Check WP core sitemap and common SEO plugin paths
		$paths = [ 'sitemap.xml', 'sitemap_index.xml', 'wp-sitemap.xml' ];
		$found = false;
		foreach ( $paths as $path ) {
			$r = wp_remote_head( $base . $path, [ 'timeout' => 8, 'sslverify' => false ] );
			if ( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) === 200 ) {
				$found = true;
				break;
			}
		}
		// Also check robots.txt for Sitemap: directive
		if ( ! $found ) {
			$r = wp_remote_get( $base . 'robots.txt', [ 'timeout' => 8, 'sslverify' => false ] );
			if ( ! is_wp_error( $r ) && stripos( wp_remote_retrieve_body( $r ), 'Sitemap:' ) !== false ) {
				$found = true;
			}
		}
		return [
			'id'          => 'sitemap',
			'label'       => 'Sitemap',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 5,
			'description' => $found
				? 'Sitemap found and linked.'
				: 'No sitemap.xml found.',
			'fix'         => $found ? null : 'Enable the WordPress core sitemap (Settings → Reading → uncheck "Discourage search engines") or install Yoast SEO / Rank Math.',
			'fix_type'    => 'info',
			'plugin_links'=> [
				[ 'name' => 'Yoast SEO', 'slug' => 'wordpress-seo' ],
				[ 'name' => 'Rank Math', 'slug' => 'seo-by-rank-math' ],
			],
		];
	}

	private static function check_llms_txt( string $base ): array {
		$url      = $base . 'llms.txt';
		$response = wp_remote_get( $url, [ 'timeout' => 8, 'sslverify' => false ] );
		$found    = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
		$body     = $found ? wp_remote_retrieve_body( $response ) : '';
		$has_content = strlen( trim( $body ) ) > 50;

		if ( $found && $has_content ) {
			$status = 'pass';
			$desc   = 'llms.txt found with content.';
			$fix    = null;
		} elseif ( $found ) {
			$status = 'warn';
			$desc   = 'llms.txt exists but appears to be empty or very short.';
			$fix    = 'Edit your llms.txt content via the llms.txt for WP plugin.';
		} else {
			$status = 'fail';
			$desc   = 'No llms.txt file found.';
			$fix    = 'Install and configure the "llms.txt for WP" plugin.';
		}

		return [
			'id'          => 'llms_txt',
			'label'       => 'llms.txt',
			'status'      => $status,
			'weight'      => 10,
			'description' => $desc,
			'fix'         => $fix,
			'fix_type'    => 'external_plugin',
			'fix_url'     => 'https://github.com/Open-WP-Club/llms-txt-for-wp',
		];
	}

	private static function check_link_headers( string $base ): array {
		$r       = wp_remote_head( $base, [ 'timeout' => 8, 'sslverify' => false ] );
		$headers = is_wp_error( $r ) ? [] : wp_remote_retrieve_headers( $r );
		$found   = ! empty( $headers['link'] );
		return [
			'id'          => 'link_headers',
			'label'       => 'Link Headers',
			'status'      => $found ? 'pass' : 'info',
			'weight'      => 3,
			'description' => $found
				? 'Link response headers found on homepage.'
				: 'No Link response headers detected.',
			'fix'         => $found ? null : 'Add Link response headers (RFC 8288) pointing to your API catalog, sitemap, or service docs. Advanced — typically requires server or custom plugin configuration.',
			'fix_type'    => 'info',
		];
	}

	// -------------------------------------------------------------------------
	// Content Accessibility
	// -------------------------------------------------------------------------
	private static function check_content( string $base ): array {
		return [
			'label'  => 'Content Accessibility',
			'checks' => [
				self::check_markdown_negotiation( $base ),
			],
		];
	}

	private static function check_markdown_negotiation( string $base ): array {
		$r = wp_remote_get( $base, [
			'timeout'   => 8,
			'sslverify' => false,
			'headers'   => [ 'Accept' => 'text/markdown' ],
		] );
		$ct    = is_wp_error( $r ) ? '' : wp_remote_retrieve_header( $r, 'content-type' );
		$found = stripos( $ct, 'text/markdown' ) !== false;
		return [
			'id'          => 'markdown_negotiation',
			'label'       => 'Markdown Negotiation',
			'status'      => $found ? 'pass' : 'info',
			'weight'      => 3,
			'description' => $found
				? 'Site returns Markdown when requested via Accept: text/markdown.'
				: 'Site does not support Markdown content negotiation.',
			'fix'         => $found ? null : 'This requires server-level or advanced plugin configuration to return text/markdown responses. Not essential for most WordPress sites.',
			'fix_type'    => 'info',
		];
	}

	// -------------------------------------------------------------------------
	// Bot Access Control
	// -------------------------------------------------------------------------
	private static function check_bot_access( string $base ): array {
		return [
			'label'  => 'Bot Access Control',
			'checks' => [
				self::check_ai_bot_rules( $base ),
				self::check_content_signals( $base ),
			],
		];
	}

	private static function check_ai_bot_rules( string $base ): array {
		$r    = wp_remote_get( $base . 'robots.txt', [ 'timeout' => 8, 'sslverify' => false ] );
		$body = is_wp_error( $r ) ? '' : strtolower( wp_remote_retrieve_body( $r ) );

		$found_bots = [];
		foreach ( self::AI_BOTS as $bot ) {
			if ( stripos( $body, strtolower( $bot ) ) !== false ) {
				$found_bots[] = $bot;
			}
		}
		$found = count( $found_bots ) >= 3;

		return [
			'id'          => 'ai_bot_rules',
			'label'       => 'AI Bot Rules in robots.txt',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 8,
			'description' => $found
				? 'AI bot rules found in robots.txt (' . implode( ', ', $found_bots ) . ').'
				: ( empty( $found_bots )
					? 'No AI-specific bot rules found in robots.txt.'
					: 'Only ' . implode( ', ', $found_bots ) . ' found — consider adding more AI crawlers.' ),
			'fix'         => $found ? null : 'Add User-agent rules for AI crawlers (GPTBot, ClaudeBot, Google-Extended, etc.) to explicitly allow or disallow them.',
			'fix_type'    => 'internal',
			'fix_url'     => admin_url( 'admin.php?page=agentready-robots' ),
		];
	}

	private static function check_content_signals( string $base ): array {
		$r    = wp_remote_get( $base . 'robots.txt', [ 'timeout' => 8, 'sslverify' => false ] );
		$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
		$found = stripos( $body, 'Content-Signal:' ) !== false || stripos( $body, 'X-Robots-Tag:' ) !== false;
		return [
			'id'          => 'content_signals',
			'label'       => 'Content Signals',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 5,
			'description' => $found
				? 'Content-Signal directives found in robots.txt.'
				: 'No Content-Signal directives found in robots.txt.',
			'fix'         => $found ? null : 'Add Content-Signal directives to robots.txt to declare your preferences for AI training, search, and AI input use.',
			'fix_type'    => 'internal',
			'fix_url'     => admin_url( 'admin.php?page=agentready-robots' ),
		];
	}

	// -------------------------------------------------------------------------
	// Protocol Discovery
	// -------------------------------------------------------------------------
	private static function check_protocol_discovery( string $base ): array {
		return [
			'label'  => 'Protocol Discovery',
			'checks' => [
				self::check_well_known( $base, 'mcp_server_card',  '/.well-known/mcp/server-card.json', 'MCP Server Card',         15, 'internal', admin_url( 'admin.php?page=agentready-mcp' ) ),
				self::check_well_known( $base, 'agent_skills',     '/.well-known/agent-skills/index.json', 'Agent Skills',         10, 'internal', admin_url( 'admin.php?page=agentready-skills' ) ),
				self::check_well_known( $base, 'a2a_agent_card',   '/.well-known/agent.json', 'A2A Agent Card',                   8,  'internal', admin_url( 'admin.php?page=agentready-a2a' ) ),
				self::check_well_known( $base, 'oauth_discovery',  '/.well-known/openid-configuration', 'OAuth / OIDC Discovery', 5,  'info', null ),
				self::check_well_known( $base, 'oauth_resource',   '/.well-known/oauth-protected-resource', 'OAuth Protected Resource', 4, 'info', null ),
				self::check_well_known( $base, 'auth_md',          '/auth.md', 'Auth.md',                                         3,  'info', null ),
				self::check_well_known( $base, 'api_catalog',      '/.well-known/api-catalog', 'API Catalog',                     4,  'info', null ),
			],
		];
	}

	private static function check_well_known( string $base, string $id, string $path, string $label, int $weight, string $fix_type, ?string $fix_url ): array {
		$url = rtrim( $base, '/' ) . $path;
		$r   = wp_remote_get( $url, [ 'timeout' => 8, 'sslverify' => false ] );
		$code  = is_wp_error( $r ) ? 0 : wp_remote_retrieve_response_code( $r );
		$found = $code >= 200 && $code < 300;

		$fix_messages = [
			'mcp_server_card'  => 'Generate your MCP Server Card in Agent Ready → MCP Server Card.',
			'agent_skills'     => 'Generate your Agent Skills index in Agent Ready → Agent Skills.',
			'a2a_agent_card'   => 'Generate your A2A Agent Card in Agent Ready → A2A Agent Card.',
			'oauth_discovery'  => 'If your site has protected APIs, publish /.well-known/openid-configuration with issuer, authorization_endpoint, token_endpoint, and jwks_uri.',
			'oauth_resource'   => 'Publish /.well-known/oauth-protected-resource with your resource identifier and authorization_servers list.',
			'auth_md'          => 'Create /auth.md with agent registration instructions for your site.',
			'api_catalog'      => 'Create /.well-known/api-catalog as application/linkset+json describing your APIs.',
		];

		return [
			'id'          => $id,
			'label'       => $label,
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => $weight,
			'description' => $found
				? "$label found at $path."
				: "$label not found at $path.",
			'fix'         => $found ? null : ( $fix_messages[ $id ] ?? "Add $path to your site." ),
			'fix_type'    => $fix_type,
			'fix_url'     => $fix_url,
		];
	}

	// -------------------------------------------------------------------------
	// SEO Basics (Schema, OG — defer to SEO plugins)
	// -------------------------------------------------------------------------
	private static function check_seo_basics( string $base ): array {
		return [
			'label'  => 'SEO & Metadata',
			'checks' => [
				self::check_open_graph( $base ),
				self::check_schema_org( $base ),
			],
		];
	}

	private static function check_open_graph( string $base ): array {
		$r    = wp_remote_get( $base, [ 'timeout' => 8, 'sslverify' => false ] );
		$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
		$found = stripos( $body, 'property="og:' ) !== false || stripos( $body, "property='og:" ) !== false;
		return [
			'id'          => 'open_graph',
			'label'       => 'Open Graph / Twitter Cards',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 6,
			'description' => $found
				? 'Open Graph meta tags found on homepage.'
				: 'No Open Graph meta tags found.',
			'fix'         => $found ? null : 'Install an SEO plugin that outputs Open Graph tags.',
			'fix_type'    => 'plugin',
			'plugin_links'=> [
				[ 'name' => 'Yoast SEO', 'slug' => 'wordpress-seo' ],
				[ 'name' => 'Rank Math', 'slug' => 'seo-by-rank-math' ],
				[ 'name' => 'All in One SEO', 'slug' => 'all-in-one-seo-pack' ],
			],
		];
	}

	private static function check_schema_org( string $base ): array {
		$r    = wp_remote_get( $base, [ 'timeout' => 8, 'sslverify' => false ] );
		$body = is_wp_error( $r ) ? '' : wp_remote_retrieve_body( $r );
		$found = stripos( $body, 'application/ld+json' ) !== false
			|| stripos( $body, 'schema.org' ) !== false;
		return [
			'id'          => 'schema_org',
			'label'       => 'Schema.org / JSON-LD',
			'status'      => $found ? 'pass' : 'fail',
			'weight'      => 8,
			'description' => $found
				? 'Schema.org structured data (JSON-LD) found on homepage.'
				: 'No Schema.org / JSON-LD structured data found.',
			'fix'         => $found ? null : 'Install an SEO plugin that outputs JSON-LD structured data.',
			'fix_type'    => 'plugin',
			'plugin_links'=> [
				[ 'name' => 'Yoast SEO', 'slug' => 'wordpress-seo' ],
				[ 'name' => 'Rank Math', 'slug' => 'seo-by-rank-math' ],
				[ 'name' => 'Schema Pro', 'slug' => 'schema-and-structured-data-for-wp' ],
			],
		];
	}

	// -------------------------------------------------------------------------
	// Commerce (informational only)
	// -------------------------------------------------------------------------
	private static function check_commerce( string $base ): array {
		$paths = [
			[ 'id' => 'x402', 'label' => 'x402 Payment',         'path' => '/', 'header_check' => '402', 'weight' => 2 ],
			[ 'id' => 'mpp',  'label' => 'MPP (Micropayments)',   'path' => '/openapi.json',              'weight' => 2 ],
			[ 'id' => 'ucp',  'label' => 'UCP',                   'path' => '/.well-known/ucp',           'weight' => 2 ],
			[ 'id' => 'acp',  'label' => 'ACP',                   'path' => '/.well-known/acp.json',      'weight' => 2 ],
		];

		$fix_messages = [
			'x402' => 'Add x402 payment middleware to enable AI agents to pay for API access via HTTP 402.',
			'mpp'  => 'Publish /openapi.json with x-payment-info extensions on payable operations.',
			'ucp'  => 'Serve /.well-known/ucp with protocol version, services, and capabilities.',
			'acp'  => 'Serve /.well-known/acp.json with protocol name, version, api_base_url, and capabilities.',
		];

		$checks = [];
		foreach ( $paths as $item ) {
			$url   = rtrim( $base, '/' ) . $item['path'];
			$r     = wp_remote_get( $url, [ 'timeout' => 6, 'sslverify' => false ] );
			$code  = is_wp_error( $r ) ? 0 : wp_remote_retrieve_response_code( $r );
			$found = $code >= 200 && $code < 300;

			if ( $item['id'] === 'mpp' && $found ) {
				$body  = wp_remote_retrieve_body( $r );
				$found = stripos( $body, 'x-payment-info' ) !== false;
			}

			$checks[] = [
				'id'          => $item['id'],
				'label'       => $item['label'],
				'status'      => $found ? 'pass' : 'info',
				'weight'      => $item['weight'],
				'description' => $found
					? $item['label'] . ' support detected.'
					: $item['label'] . ' not detected (optional).',
				'fix'         => $found ? null : $fix_messages[ $item['id'] ],
				'fix_type'    => 'info',
			];
		}

		return [
			'label'  => 'Agentic Commerce (Optional)',
			'checks' => $checks,
		];
	}
}
