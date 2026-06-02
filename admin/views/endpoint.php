<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ar-wrap">
	<h1 class="ar-page-title"><?php echo esc_html( $endpoint_label ); ?></h1>
	<p class="ar-page-desc">
		Served at <code><?php echo esc_html( $endpoint_path ); ?></code>
		<?php if ( AgentReady_Well_Known::get_endpoint_json( $endpoint_id ) ) : ?>
			— <a href="<?php echo esc_url( $endpoint_url ); ?>" target="_blank">View live ↗</a>
		<?php endif; ?>
	</p>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $endpoint_label ); ?> saved and published.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php echo esc_html( $endpoint_label ); ?> deleted. The URL now returns 404.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'invalid_json' ) : ?>
		<div class="notice notice-error is-dismissible"><p>Invalid JSON — please fix the syntax and try again.</p></div>
	<?php endif; ?>

	<div class="ar-two-col">
		<div class="ar-col-main">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="agentready_save_endpoint">
				<input type="hidden" name="endpoint_id" value="<?php echo esc_attr( $endpoint_id ); ?>">
				<?php wp_nonce_field( 'agentready_endpoint' ); ?>
				<label for="ar-ep-json" class="ar-label">JSON Content</label>
				<textarea
					id="ar-ep-json"
					name="endpoint_json"
					class="ar-textarea ar-textarea--code ar-textarea--json"
					rows="25"
					spellcheck="false"
				><?php echo esc_textarea( $default ); ?></textarea>
				<div class="ar-actions">
					<button type="submit" class="button button-primary">Save &amp; Publish</button>
				</div>
			</form>
		</div>

		<div class="ar-col-side">
			<div class="ar-sidebar-box">
				<h3>Regenerate defaults</h3>
				<p>Overwrites the editor with a fresh auto-generated template based on current site data.</p>
				<button type="button" class="button button-secondary" id="ar-regen-btn">Regenerate</button>
			</div>

			<?php if ( AgentReady_Well_Known::get_endpoint_json( $endpoint_id ) ) : ?>
				<div class="ar-sidebar-box ar-sidebar-box--danger">
					<h3>Delete</h3>
					<p>Removes the published file. The URL will return 404.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="agentready_delete_endpoint">
						<input type="hidden" name="endpoint_id" value="<?php echo esc_attr( $endpoint_id ); ?>">
						<?php wp_nonce_field( 'agentready_endpoint' ); ?>
						<button type="submit" class="button ar-btn--danger">Delete</button>
					</form>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
(function() {
	var defaultJson = <?php echo wp_json_encode( $default ); ?>;
	document.getElementById('ar-regen-btn').addEventListener('click', function() {
		if (confirm('Replace current content with a fresh generated template?')) {
			document.getElementById('ar-ep-json').value = defaultJson;
		}
	});
})();
</script>
