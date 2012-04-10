<?php

/**
 * BuddyPress - WDF Directory
 *
 * @package BuddyPress_Skeleton_Component
 */

?>

<?php get_header( 'buddypress' ); ?>

	<?php do_action( 'bp_before_directory_wdf_page' ); ?>

	<div id="content">
		<div class="padder">

		<?php do_action( 'bp_before_directory_wdf' ); ?>

		<form action="" method="post" id="wdf-directory-form" class="dir-form">

			<h3><?php _e( 'High Fives Directory', 'wdf' ); ?></h3>

			<?php do_action( 'bp_before_directory_wdf_content' ); ?>

			<?php do_action( 'template_notices' ); ?>

			<div class="item-list-tabs no-ajax" role="navigation">
				<ul>
					<li class="selected" id="groups-all"><a href="<?php echo trailingslashit( bp_get_root_domain() . '/' . bp_get_wdf_root_slug() ); ?>"><?php printf( __( 'All High Fives <span>%s</span>', 'buddypress' ), bp_wdf_get_total_donation_count() ); ?></a></li>

					<?php do_action( 'bp_wdf_directory_wdf_filter' ); ?>

				</ul>
			</div><!-- .item-list-tabs -->

			<div id="wdf-dir-list" class="wdf dir-list">

				<?php bp_core_load_template( 'wdf/wdf-loop' ); ?>

			</div><!-- #wdf-dir-list -->

			<?php do_action( 'bp_directory_wdf_content' ); ?>

			<?php wp_nonce_field( 'directory_wdf', '_wpnonce-wdf-filter' ); ?>

			<?php do_action( 'bp_after_directory_wdf_content' ); ?>

		</form><!-- #wdf-directory-form -->

		<?php do_action( 'bp_after_directory_wdf' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_directory_wdf_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>

