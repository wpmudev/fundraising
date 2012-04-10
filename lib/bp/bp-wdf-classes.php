<?php

/**
 * This function should include all classes and functions that access the database.
 * In most BuddyPress components the database access classes are treated like a model,
 * where each table has a class that can be used to create an object populated with a row
 * from the corresponding database table.
 *
 * By doing this you can easily save, update and delete records using the class, you're also
 * abstracting database access.
 *
 * This function uses WP_Query and wp_insert_post() to fetch and store data, using WordPress custom
 * post types. This method for data storage is highly recommended, as it assures that your data
 * will be maximally compatible with WordPress's security and performance optimization features, in
 * addition to making your plugin easier to extend for other developers. The suggested
 * implementation here (where the WP_Query object is set as the query property on the
 * BP_WDF_Donation object in get()) is one suggested implementation.
 */

class BP_WDF_Donation {
	var $id;
	var $funder_id;
	var $recipient_id;
	var $date;
	var $query;

	/**
	 * bp_wdf_tablename()
	 *
	 * This is the constructor, it is auto run when the class is instantiated.
	 * It will either create a new empty object if no ID is set, or fill the object
	 * with a row from the table if an ID is provided.
	 */
	function __construct( $args = array() ) {
		// Set some defaults
		$defaults = array(
			'id'		=> 0,
			'funder_id' => 0,
			'recipient_id'  => 0,
			'date' 		=> date( 'Y-m-d H:i:s' )
		);

		// Parse the defaults with the arguments passed
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		if ( $id ) {
			$this->id = $id;
			$this->populate( $this->id );
		} else {
			foreach( $r as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * populate()
	 *
	 * This method will populate the object with a row from the database, based on the
	 * ID passed to the constructor.
	 */
	function populate() {
		global $wpdb, $bp, $creds;

		if ( $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->wdf->table_name} WHERE id = %d", $this->id ) ) ) {
			$this->funder_id = $row->funder_id;
			$this->recipient_id  = $row->recipient_id;
			$this->date 	     = $row->date;
		}
	}

	/**
	 * save()
	 *
	 * This method will save an object to the database. It will dynamically switch between
	 * INSERT and UPDATE depending on whether or not the object already exists in the database.
	 */

	function save() {
		global $wpdb, $bp;

		/***
		 * In this save() method, you should add pre-save filters to all the values you are
		 * saving to the database. This helps with two things -
		 *
		 * 1. Blanket filtering of values by plugins (for example if a plugin wanted to
		 * force a specific value for all saves)
		 *
		 * 2. Security - attaching a wp_filter_kses() call to all filters, so you are not
		 * saving potentially dangerous values to the database.
		 *
		 * It's very important that for number 2 above, you add a call like this for each
		 * filter to 'bp-wdf-filters.php'
		 *
		 *   add_filter( 'wdf_data_fieldname1_before_save', 'wp_filter_kses' );
		 */

		$this->funder_id = apply_filters( 'bp_wdf_data_funder_id_before_save', $this->funder_id, $this->id );
		$this->recipient_id  = apply_filters( 'bp_wdf_data_recipient_id_before_save', $this->recipient_id, $this->id );
		$this->date	     = apply_filters( 'bp_wdf_data_date_before_save', $this->date, $this->id );

		// Call a before save action here
		do_action( 'bp_wdf_data_before_save', $this );

		if ( $this->id ) {
			// Set up the arguments for wp_insert_post()
			$wp_update_post_args = array(
				'ID'		=> $this->id,
				'post_author'	=> $this->funder_id,
				'post_title'	=> sprintf( __( '%1$s high-fives %2$s', 'wdf' ), bp_core_get_user_displayname( $this->funder_id ), bp_core_get_user_displayname( $this->recipient_id ) )
			);

			// Save the post
			$result = wp_update_post( $wp_update_post_args );

			// We'll store the reciever's ID as postmeta
			if ( $result ) {
				update_post_meta( $result, 'bp_wdf_recipient_id', $this->recipient_id );
			}
		} else {
			// Set up the arguments for wp_insert_post()
			$wp_insert_post_args = array(
				'post_status'	=> 'publish',
				'post_type'	=> 'donation',
				'post_author'	=> $this->funder_id,
				'post_title'	=> sprintf( __( '%1$s donated to %2$s', 'wdf' ), bp_core_get_user_displayname( $this->funder_id ), bp_core_get_user_displayname( $this->recipient_id ) )
			);

			// Save the post
			$result = wp_insert_post( $wp_insert_post_args );

			// We'll store the reciever's ID as postmeta
			if ( $result ) {
				update_post_meta( $result, 'bp_wdf_recipient_id', $this->recipient_id );
			}
		}

		/* Add an after save action here */
		do_action( 'bp_wdf_data_after_save', $this );

		return $result;
	}

	/**
	 * Fire the WP_Query
	 *
	 * @package BuddyPress_Skeleton_Component
	 * @since 1.6
	 */
	function get( $args = array() ) {
		// Only run the query once
		if ( empty( $this->query ) ) {
			$defaults = array(
				'funder_id'	=> 0,
				'recipient_id'	=> 0,
				'per_page'	=> 10,
				'paged'		=> 1
			);

			$r = wp_parse_args( $args, $defaults );
			extract( $r );

			$query_args = array(
				'post_status'	 => 'publish',
				'post_type'	 => 'donation',
				'posts_per_page' => $per_page,
				'paged'		 => $paged,
				'meta_query'	 => array()
			);

			// Some optional query args
			// Note that some values are cast as arrays. This allows you to query for multiple
			// authors/recipients at a time
			if ( $funder_id ) {
				$query_args['author'] = (array)$funder_id;
			}

			// We can filter by postmeta by adding a meta_query argument. Note that
			if ( $recipient_id ) {
				$query_args['meta_query'][] = array(
					'key'	  => 'bp_wdf_recipient_id',
					'value'	  => (array)$recipient_id,
					'compare' => 'IN' // Allows $recipient_id to be an array
				);
			}

			// Run the query, and store as an object property, so we can access from
			// other methods
			$this->query = new WP_Query( $query_args );

			// Let's also set up some pagination
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( 'items_page', '%#%' ),
				'format' => '',
				'total' => ceil( (int) $this->query->found_posts / (int) $this->query->query_vars['posts_per_page'] ),
				'current' => (int) $paged,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size' => 1
			) );
		}
	}

	/**
	 * Part of our bp_wdf_has_donations() loop
	 *
	 * @package BuddyPress_Skeleton_Component
	 * @since 1.6
	 */
	function have_posts() {
		return $this->query->have_posts();
	}

	/**
	 * Part of our bp_wdf_has_donations() loop
	 *
	 * @package BuddyPress_Skeleton_Component
	 * @since 1.6
	 */
	function the_post() {
		return $this->query->the_post();
	}

	/**
	 * delete()
	 *
	 * This method will delete the corresponding row for an object from the database.
	 */
	function delete() {
		return wp_trash_post( $this->id );
	}

	/* Static Functions */

	/**
	 * Static functions can be used to bulk delete items in a table, or do something that
	 * doesn't necessarily warrant the instantiation of the class.
	 *
	 * Look at bp-core-classes.php for examples of mass delete.
	 */

	function delete_all() {

	}

	function delete_by_user_id() {

	}
}

?>