<?php

/*
 * In This File: 
 *    The widget class with the four horsemen of WordPress Widgets:
 *      __construct()
 *      widget()
 *      form()
 *      update()
 *
 *    Important registration and action filters at the end of the file. 
 *
 * How It Works: 
 *    Every time a post is updated or deleted, or the widget settings are updated, 
 *    a new 'PixelPatch_Calendar' is instantiated and saved as a transient in the database. 
 *
 *    The PixelPatch_Calendar WP_Query()s the database for all posts from the last year 
 *      (the last 365 days, NOT the previous calendar year). 
 *    It then builds a grid calendar of every day, each week divided into a separate semantic <ul> column.
 *    If the day has_post()s, a daily archive link is created and put into that day's <li>.
 *    Lastly, styling for coloring the grid is dumped right into the html
 */


class PixelPatch_Widget extends WP_Widget {


	public function __construct() {

		parent::__construct(
			'pixelpatch',
			__( 'PixelPatch', 'patchworks' ),
			array( 'description' => __( 'A pixel calendar widget', 'patchworks' ) )
		);

	}




	public function widget( $args, $instance ) {

		wp_enqueue_style( 'pixelpatch' );

		$title = apply_filters( 'widget_title', $instance['title'] );

		// $styles are taken from the form and given to the PixelPatch_Calendar later
		$style = [ 'color'      => $instance['color'],
							 'hovercolor' => $instance['hovercolor'] ];

		update_option( 'pixelpatch_calendar_style', $style);

		echo $args['before_widget'];

		// If there is a title, print it out 
		if ( ! empty( $title ) )
			echo $args['before_title'] . '<h2>' . $title . '</h2>' .$args['after_title'];

		// The first important bit of logic. 
		// If the 'patched_up_pixel_calendar' transient doesn't exist in the db, make a new one and save it
		// Note the calendar is instantiated with the styles from before.
		if ( ! get_transient( 'pixelpatch_calendar' ) ) 
			PixelPatch::update_transient();

		// Either way, now is the time to print out the saved calendar
		echo get_transient( 'pixelpatch_calendar' );
		
		echo $args['after_widget'];
	}




	public function form( $instance ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'pixelpatch' );

		// Grab the existing styling variables if they exist
		if ( isset( $instance ) ) extract( $instance );

	?>
		<h3>Settings</h3>
		<p><?php // Standard Title form ?>
			<label for="<?php echo $this->get_field_id('title');?>">Title:</label> 
			<input  type="text"
							class="widefat"
							id="<?php echo $this->get_field_id('title'); ?>"
							name="<?php echo $this->get_field_name('title'); ?>"
							value="<?php if ( isset($title) ) echo esc_attr($title); ?>" />
		</p>
		<p><?php // Color form for base color. Will be js color picker in the future ?>
			<label for="<?php echo $this->get_field_id('color');?>">Pixel Base Color:</label> 
			<br />
			<input  type="text"
							class="color"
							id="<?php echo $this->get_field_id('color'); ?>"
							name="<?php echo $this->get_field_name('color'); ?>"
							maxlength="6"
							value="<?php if ( isset( $color ) ) echo esc_attr( $color ); ?>" />
		</p>
		<p><?php // Hovercolor form. Again will be js color picker in the future ?>
			<label for="<?php echo $this->get_field_id( 'hovercolor' );?>">Pixel Hover Color:</label> 
			<br />
			<input  type="text"
							class="color"
							id="<?php echo $this->get_field_id( 'hovercolor' ); ?>"
							name="<?php echo $this->get_field_name( 'hovercolor' ); ?>"
							maxlength="6"
							value="<?php if ( isset( $hovercolor ) ) echo esc_attr( $hovercolor ); ?>" />
		</p>
		<h3>Preview</h3>
		<div class="widget_patched_up_pixel_calendar"> 
			<?php
				if ( ! get_transient( 'pixelpatch_calendar' ) ) 
					PixelPatch::update_transient();

				echo get_transient( 'pixelpatch_calendar' );
			?>
		</div>
		<?php
	}




	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		// Fields
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['color'] = strip_tags( $new_instance['color'] );
		$instance['hovercolor'] = strip_tags( $new_instance['hovercolor'] );

		$style = [ 'color'      => $instance['color'],
							 'hovercolor' => $instance['hovercolor'] ];

		update_option( 'pixelpatch_calendar_style', $style );

		PixelPatch::update_transient();;
	
		return $instance;
	}

}