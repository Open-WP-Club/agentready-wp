<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ar-wrap">
	<h1 class="ar-page-title">robots.txt Rules</h1>
	<p class="ar-page-desc">
		These lines are appended to WordPress's auto-generated <code>robots.txt</code>.
		Add AI bot rules and Content-Signal directives here.
	</p>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>robots.txt additions saved.</p></div>
	<?php endif; ?>

	<div class="ar-two-col">
		<div class="ar-col-main">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="agentready_save_robots_additions">
				<?php wp_nonce_field( 'agentready_robots' ); ?>
				<label for="ar-robots-additions" class="ar-label">Additions to robots.txt</label>
				<textarea
					id="ar-robots-additions"
					name="robots_additions"
					class="ar-textarea ar-textarea--code"
					rows="20"
					spellcheck="false"
				><?php echo esc_textarea( $additions ); ?></textarea>
				<div class="ar-actions">
					<button type="submit" class="button button-primary">Save</button>
					<a href="<?php echo esc_url( $robots_url ); ?>" target="_blank" class="button">View robots.txt ↗</a>
				</div>
			</form>
		</div>

		<div class="ar-col-side">
			<div class="ar-sidebar-box">
				<h3>AI Bot rules template</h3>
				<p>Click to insert a starter template with common AI crawlers.</p>
				<button type="button" class="button button-secondary" id="ar-insert-ai-bots">Insert AI Bot Template</button>
				<script>
				document.getElementById('ar-insert-ai-bots').addEventListener('click', function() {
					var ta = document.getElementById('ar-robots-additions');
					var tpl = [
						'# AI Crawlers',
						'User-agent: GPTBot',
						'Allow: /',
						'',
						'User-agent: ChatGPT-User',
						'Allow: /',
						'',
						'User-agent: ClaudeBot',
						'Allow: /',
						'',
						'User-agent: Claude-Web',
						'Allow: /',
						'',
						'User-agent: anthropic-ai',
						'Allow: /',
						'',
						'User-agent: Google-Extended',
						'Allow: /',
						'',
						'User-agent: PerplexityBot',
						'Allow: /',
						'',
						'# Content Signals',
						'Content-Signal: ai-train=true',
						'Content-Signal: search=true',
						'Content-Signal: ai-input=true',
					].join('\n');
					ta.value = (ta.value ? ta.value + '\n\n' : '') + tpl;
				});
				</script>
			</div>

			<div class="ar-sidebar-box">
				<h3>Content Signals</h3>
				<p>Declare your preferences for AI use of your content:</p>
				<ul>
					<li><code>ai-train=true/false</code> — allow/deny use in AI training</li>
					<li><code>search=true/false</code> — allow/deny AI search indexing</li>
					<li><code>ai-input=true/false</code> — allow/deny as AI context input</li>
				</ul>
			</div>
		</div>
	</div>
</div>
