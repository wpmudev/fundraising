<?php

/**
 *
 * @package BuddyPress_Skeleton_Component
 * @since 1.6
 */

?>

<?php do_action( 'bp_before_wdf_loop' ); ?>

<?php if ( bp_wdf_has_items( bp_ajax_querystring( 'wdf' ) ) ) : ?>
<?php // global $items_template; var_dump( $items_template ) ?>
	<div id="pag-top" class="pagination">

		<div class="pag-count" id="wdf-dir-count-top">

			<?php bp_wdf_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="wdf-dir-pag-top">

			<?php bp_wdf_item_pagination(); ?>

		</div>

	</div>

	<?php do_action( 'bp_before_directory_wdf_list' ); ?>

	<ul id="wdf-list" class="item-list" role="main">

	<?php while ( bp_wdf_has_items() ) : bp_wdf_the_item(); ?>

		<li>
			<div class="item-avatar">
				<?php bp_wdf_funder_avatar( 'type=thumb&width=50&height=50' ); ?>
			</div>

			<div class="item">
				<div class="item-title"><?php bp_wdf_donation_title() ?></div>

				<?php do_action( 'bp_directory_wdf_item' ); ?>

			</div>

			<div class="clear"></div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php do_action( 'bp_after_directory_wdf_list' ); ?>

	<div id="pag-bottom" class="pagination">

		<div class="pag-count" id="wdf-dir-count-bottom">

			<?php bp_wdf_pagination_count(); ?>

		</div>

		<div class="pagination-links" id="wdf-dir-pag-bottom">

			<?php bp_wdf_item_pagination(); ?>

		</div>

	</div>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'There were no donations found.', 'wdf' ); ?></p>
	</div>

<?php endif; ?>

<?php do_action( 'bp_after_wdf_loop' ); ?>
