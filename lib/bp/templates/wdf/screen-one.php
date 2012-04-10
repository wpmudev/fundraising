<?php get_header() ?>

	<div id="content">
		<div class="padder">

			<div id="item-header">
				<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav">
					<ul>
						<?php bp_get_displayed_user_nav() ?>
					</ul>
				</div>
			</div>

			<div id="item-body">

				<div class="item-list-tabs no-ajax" id="subnav">
					<ul>
						<?php bp_get_options_nav() ?>
					</ul>
				</div>

				<h4><?php _e( 'Welcome to Screen One', 'wdf' ) ?></h4>
				<?php /*?><p><?php printf( __( 'Send %s a <a href="%s" title="Send high-five!">high-five!</a>', 'wdf' ), bp_get_displayed_user_fullname(), wp_nonce_url( bp_displayed_user_domain() . bp_current_component() . '/screen-one/send-donation/', 'bp_wdf_send_donation' ) ) ?></p><?php */?>

				<?php if ( $donations = bp_wdf_get_donations_for_user( bp_displayed_user_id() ) ) : ?>
					<h4><?php _e( 'Received High Fives!', 'wdf' ) ?></h4>

					<table id="bp_wdf_donations">
						<?php foreach ( $donations as $user_id ) : ?>
						<tr>
							<td width="1%"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'width' => 25, 'height' => 25 ) ) ?></td>
							<td>&nbsp; <?php echo bp_core_get_userlink( $user_id ) ?></td>
			 			</tr>
						<?php endforeach; ?>
					</table>
				<?php endif; ?>

			</div><!-- #item-body -->

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>