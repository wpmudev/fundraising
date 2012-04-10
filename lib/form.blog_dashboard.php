<div id="wdf_dashboard" class="wrap">
	<div id="icon-wdf-admin" class="icon32"><br></div>
	<h2><?php echo __('Getting Started Guide','wdf'); ?></h2>
	<p><?php echo __('The Fundraising plugin can help you fund your important projects.','wdf') ?></p> 
	<div class="metabox-holder">
		<div class="postbox">
			<h3 class="hndle"><span>First Time Setup Guide</span></h3>
			<div class="inside">
				<div class="updated below-h2">
					<p>Welcome to the Donations Plugin.  Follow the steps below to start taking donations.</p>
				</div>
				<ol id="wdf_steps">
					<li>Configure your general, payment and email settings.<a href="<?php echo admin_url('edit.php?post_type=funder&page=wdf_settings'); ?>" class="button wdf_goto_step">Configure Settings</a></li>
					<li>Create your first fundraiser, set a goal, and choose a display style.<a href="<?php echo admin_url('post-new.php?post_type=funder'); ?>" class="button wdf_goto_step">Create A Fundraiser</a></li>
					<li>Insert a widget to handle simple donations.<a href="<?php echo admin_url('widgets.php'); ?>" class="button wdf_goto_step">Add A Widget</a></li>
					<li>Add a fundraising shortcode to an existing post or page<code>[fundraiser id=""][donate_button paypal_email="" button_type="default"]</code></li>
				</ol>
			</div>
		</div>
		<div class="postbox">
			<h3 class="hndle"><span><?php echo __('Need Help?','wdf'); ?></span></h3>
			<div class="inside">
				<form action="<?php echo admin_url('edit.php?post_type=funder&page=wdf'); ?>" method="post">
					<label>Restart the getting started walkthrough?</label>
					<input type="submit" name="wdf_restart_tutorial" class="button" value="Restart the Tutorial" />
				</form>
			</div>
		</div>
	</div>
</div><!-- #wdf_dashboard -->