# Agent Ready for WordPress

Scan your WordPress site for AI agent readiness and publish the discovery files that AI agents, crawlers, and agentic frameworks need to interact with your content.

![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue?logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple?logo=php)
![License](https://img.shields.io/badge/License-GPL%20v2-green)
![GitHub release](https://img.shields.io/github/v/release/Open-WP-Club/agentready-wp)

## Features

### AI Readiness Scanner

- **20+ Automated Checks**: Scans your live site across six categories — Discoverability, Content Accessibility, Bot Access Control, Protocol Discovery, SEO & Metadata, and Agentic Commerce
- **Weighted Score (0–100)**: Aggregates check results into a single readiness score with a clear label (Not Ready → Improving → Ready)
- **Smart Fix Guidance**: Each failed check shows exactly what's missing and how to fix it — internal link, plugin recommendation, or external resource
- **1-Hour Result Cache**: Scan results are cached in a transient to avoid hammering your own site; a "Re-scan Now" button forces a fresh run

### Bot Access Control

- **AI Bot Rules Editor**: Add `User-agent` rules for GPTBot, ClaudeBot, ChatGPT-User, Google-Extended, PerplexityBot, and more directly from the admin
- **One-Click Template**: Insert a pre-built template covering all major AI crawlers with a single button click
- **Content-Signal Directives**: Declare your site's preferences for AI training, search indexing, and AI input use (`ai-train`, `search`, `ai-input`)
- **Non-Destructive Appending**: Additions are appended via the WordPress `robots_txt` filter — your existing `robots.txt` is never overwritten

### Protocol Discovery Files

- **MCP Server Card**: Publish a Model Context Protocol server card at `/.well-known/mcp/server-card.json` with auto-generated defaults from your site data
- **Agent Skills Index**: Publish an Agent Skills discovery index at `/.well-known/agent-skills/index.json`
- **A2A Agent Card**: Publish a Google Agent-to-Agent card at `/.well-known/agent.json`
- **WordPress-Native Serving**: All files are stored in `wp_options` and served via WordPress rewrite rules — no physical files, no filesystem permissions required
- **JSON Editor with Validation**: Edit the raw JSON in the admin; invalid JSON is rejected with a clear error before saving

### SEO & Metadata Checks

- **Open Graph Detection**: Checks for `og:` meta tags on your homepage and links to recommended SEO plugins if missing
- **Schema.org / JSON-LD Detection**: Verifies structured data is present and links to Yoast SEO, Rank Math, or Schema Pro if not
- **Plugin-Aware Recommendations**: Generates direct WordPress plugin install links so you can act on recommendations in one click

### llms.txt Support

- **Detection**: Checks whether `/llms.txt` exists and has meaningful content
- **Recommendation**: Points directly to the [llms.txt for WP](https://github.com/Open-WP-Club/llms-txt-for-wp) plugin for generation — no duplication of solved problems

## Checks Reference

| Category | Check | Weight |
|---|---|---|
| Discoverability | robots.txt | 5 |
| Discoverability | Sitemap | 5 |
| Discoverability | llms.txt | 10 |
| Discoverability | Link Headers | 3 |
| Content Accessibility | Markdown Negotiation | 3 |
| Bot Access Control | AI Bot Rules | 8 |
| Bot Access Control | Content Signals | 5 |
| Protocol Discovery | MCP Server Card | 15 |
| Protocol Discovery | Agent Skills | 10 |
| Protocol Discovery | A2A Agent Card | 8 |
| Protocol Discovery | OAuth / OIDC Discovery | 5 |
| Protocol Discovery | OAuth Protected Resource | 4 |
| Protocol Discovery | Auth.md | 3 |
| Protocol Discovery | API Catalog | 4 |
| SEO & Metadata | Open Graph / Twitter Cards | 6 |
| SEO & Metadata | Schema.org / JSON-LD | 8 |
| Agentic Commerce | x402 | 2 |
| Agentic Commerce | MPP | 2 |
| Agentic Commerce | UCP | 2 |
| Agentic Commerce | ACP | 2 |

## Requirements

- WordPress 6.4 or higher
- PHP 8.1 or higher

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release ZIP from [GitHub Releases](https://github.com/Open-WP-Club/agentready-wp/releases)
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `agentready-wp` folder to `/wp-content/plugins/`
3. Go to **Plugins** in your WordPress admin
4. Find "Agent Ready" and click **Activate**

### Method 3: Git Clone

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Open-WP-Club/agentready-wp.git agentready-wp
```

After activating the plugin, visit **Settings > Permalinks** and click **Save Changes** to flush rewrite rules — this ensures the `/.well-known/` endpoints start responding immediately.

## Usage Guide

### Getting Started

1. **Activate the plugin** — rewrite rules are registered automatically on activation
2. **Open Agent Ready** in the WordPress admin sidebar
3. **Read the Dashboard** — the scan runs automatically on first load
4. **Work through the Fail items** — start with the highest-weight checks for maximum score improvement

### Dashboard

The dashboard shows your overall score and a table of every check grouped by category. Each row includes:

- **Status badge** — Pass (green), Fail (red), Warning (yellow), N/A (grey)
- **Check name**
- **Description** — what was found (or not found)
- **Fix action** — internal link, plugin install link, or external documentation

Click **Re-scan Now** to force a fresh scan after making changes.

### robots.txt Rules

Go to **Agent Ready > robots.txt Rules** to manage AI-specific additions:

1. Click **Insert AI Bot Template** to insert a ready-made block covering all major AI crawlers
2. Edit the `Allow` / `Disallow` rules as needed for your site's policy
3. Add `Content-Signal:` directives to declare your AI content preferences
4. Click **Save** — changes appear in your live `robots.txt` immediately
5. Click **View robots.txt ↗** to verify the output

### MCP Server Card / Agent Skills / A2A Agent Card

Go to the relevant submenu under **Agent Ready**:

1. The editor is pre-populated with an auto-generated template from your site's name, description, and URL
2. Edit the JSON to match your site's actual capabilities
3. Click **Save & Publish** — the endpoint goes live instantly
4. Verify by clicking **View live ↗** next to the endpoint path

To take an endpoint offline, click **Delete** — the URL returns 404 until you publish again.

## Technical Details

### How Discovery Files Are Served

Protocol discovery files (`/.well-known/*` and `/llms.txt`) are served via WordPress rewrite rules, not physical files on disk. Content is stored in `wp_options` and output with the correct `Content-Type` headers through `template_redirect`. This means:

- No filesystem write permissions required
- Files update instantly when saved in the admin
- Files disappear instantly when deleted
- Works behind any caching layer that respects query vars

### Scan Mechanism

The scanner uses the WordPress HTTP API (`wp_remote_get`) to make requests against the site's own URLs. This means it tests the site exactly as an external agent would see it — through the full WordPress request stack, with any active caching, CDN, or security rules in effect.

Results are stored in a transient for one hour. Forced re-scans delete the transient before running.

### robots.txt Integration

The plugin hooks into WordPress's built-in `robots_txt` filter at priority 20. This means it appends after WordPress core and after any SEO plugin that also hooks into `robots_txt`. Your existing rules are never modified.

## Development

### Local Setup

```bash
# Clone the repository
git clone https://github.com/Open-WP-Club/agentready-wp.git

# Install in your local WordPress plugins directory
cp -r agentready-wp /path/to/wordpress/wp-content/plugins/

# Activate via WP-CLI
wp plugin activate agentready-wp

# Flush rewrite rules
wp rewrite flush
```

### File Structure

```
agentready.php                  Main plugin file, activation hooks
includes/
  class-scanner.php             All check logic, transient caching
  class-well-known.php          .well-known/ endpoint manager
admin/
  class-admin.php               Admin menus, form handlers, robots filter
  views/
    dashboard.php               Scan results table + score card
    robots.php                  robots.txt additions editor
    endpoint.php                Generic .well-known JSON editor
assets/
  css/admin.css                 Admin styles
  js/admin.js                   JSON auto-formatter
```

## Contributing

We welcome contributions from the community.

### Reporting Issues

1. Check existing [issues](https://github.com/Open-WP-Club/agentready-wp/issues)
2. Create a new issue with detailed information
3. Include WordPress version, PHP version, and any error logs
4. Describe the expected vs actual behaviour

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes following WordPress coding standards
4. Test on a clean WordPress installation
5. Commit with a clear message: `git commit -m 'Add your feature'`
6. Push to your branch: `git push origin feature/your-feature`
7. Open a Pull Request with a description of what changed and why

## License

This project is licensed under the GNU General Public License v2.0 — see the [LICENSE](LICENSE) file for details.

## Support

- [GitHub Issues](https://github.com/Open-WP-Club/agentready-wp/issues) — bug reports and feature requests
- [GitHub Discussions](https://github.com/Open-WP-Club/agentready-wp/discussions) — questions and community help

### Before Asking for Help

1. **Update to the latest version** — many issues are resolved in updates
2. **Flush rewrite rules** — go to Settings > Permalinks and click Save Changes after any activation/deactivation
3. **Check existing issues** — your question may already be answered
4. **Test with a default theme** — rule out theme conflicts
5. **Disable other plugins** — identify plugin conflicts
6. **Provide system info** — WordPress version, PHP version, and active plugins list
