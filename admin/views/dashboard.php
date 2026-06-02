<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ar-wrap">
	<h1 class="ar-page-title">Agent Ready</h1>

	<?php if ( isset( $_GET['scanned'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Scan complete.</p></div>
	<?php endif; ?>

	<!-- Score card -->
	<div class="ar-score-card">
		<div class="ar-score-circle <?php echo esc_attr( 'ar-score--' . strtolower( str_replace( ' ', '-', AgentReady_Admin::score_label( $results['score'] ) ) ) ); ?>">
			<span class="ar-score-number"><?php echo esc_html( $results['score'] ); ?></span>
			<span class="ar-score-max">/100</span>
		</div>
		<div class="ar-score-meta">
			<h2 class="ar-score-label"><?php echo esc_html( AgentReady_Admin::score_label( $results['score'] ) ); ?></h2>
			<p class="ar-score-date">
				Last scanned: <?php echo esc_html( human_time_diff( $results['scanned_at'] ) . ' ago' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="agentready_scan">
				<?php wp_nonce_field( 'agentready_scan' ); ?>
				<button type="submit" class="button button-primary">Re-scan Now</button>
			</form>
		</div>
	</div>

	<!-- Results by category -->
	<?php foreach ( $results['categories'] as $cat_key => $category ) : ?>
		<?php
		$cat_checks = $category['checks'];
		$cat_pass   = count( array_filter( $cat_checks, fn( $c ) => $c['status'] === 'pass' ) );
		$cat_total  = count( array_filter( $cat_checks, fn( $c ) => $c['weight'] > 0 ) );
		?>
		<div class="ar-category">
			<div class="ar-category-header">
				<h2 class="ar-category-title"><?php echo esc_html( $category['label'] ); ?></h2>
				<span class="ar-category-count"><?php echo esc_html( "$cat_pass / $cat_total passed" ); ?></span>
			</div>
			<table class="ar-checks-table widefat fixed striped">
				<thead>
					<tr>
						<th class="ar-col-status">Status</th>
						<th class="ar-col-check">Check</th>
						<th class="ar-col-detail">Details / Fix</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cat_checks as $check ) : ?>
						<tr class="ar-check-row ar-check--<?php echo esc_attr( $check['status'] ); ?>">
							<td class="ar-col-status"><?php echo AgentReady_Admin::status_badge( $check['status'] ); ?></td>
							<td class="ar-col-check"><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
							<td class="ar-col-detail">
								<span class="ar-check-desc"><?php echo esc_html( $check['description'] ); ?></span>
								<?php if ( ! empty( $check['fix'] ) ) : ?>
									<br><span class="ar-check-fix">
										<?php if ( $check['fix_type'] === 'internal' && ! empty( $check['fix_url'] ) ) : ?>
											<a href="<?php echo esc_url( $check['fix_url'] ); ?>" class="button button-small ar-fix-btn">Fix →</a>
										<?php elseif ( $check['fix_type'] === 'external_plugin' && ! empty( $check['fix_url'] ) ) : ?>
											<span class="ar-fix-info"><?php echo esc_html( $check['fix'] ); ?></span>&nbsp;
											<a href="<?php echo esc_url( $check['fix_url'] ); ?>" target="_blank" rel="noopener" class="button button-small">View plugin ↗</a>
										<?php elseif ( $check['fix_type'] === 'plugin' && ! empty( $check['plugin_links'] ) ) : ?>
											<?php echo esc_html( $check['fix'] ); ?>&nbsp;
											<?php foreach ( $check['plugin_links'] as $pl ) : ?>
												<?php echo AgentReady_Admin::plugin_install_link( $pl ); ?>&nbsp;
											<?php endforeach; ?>
										<?php else : ?>
											<span class="ar-fix-info"><?php echo esc_html( $check['fix'] ); ?></span>
										<?php endif; ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>
</div>
