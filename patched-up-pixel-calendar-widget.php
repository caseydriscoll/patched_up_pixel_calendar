<?php

/* Plugin Name: Patched Up Pixel Calendar
 * Plugin URI: http://patchedupcreative.com/plugins/pixel-calendar
 * Description: A widget for displaying all your posts as a minimalist raster calendar of 'pixels' similar to Github's 'contribution calendar'
 * Version: 1.1.2
 * Date: 09-27-2013
 * Author: Casey Patrick Driscoll
 * Author URI: http://caseypatrickdriscoll.com
 *
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
 *    a new 'Patched_Up_Pixel_Calendar' is instantiated and saved as a transient in the database. 
 *
 *    The Patched_Up_Pixel_Calendar WP_Query()s the database for all posts from the last year 
 *      (the last 365 days NOT the previous calendar year). 
 *    It then builds a grid calendar of every day, each week divided into a separate semantic <ul> column.
 *    If the day has_post()s, a daily archive link is created and put into that day's <li>.
 *    Lastly, styling for coloring the grid is dumped right into the html, 
 *      because I'm not sure how to appropriately do that yet. 
 *			(I would like to write to an external style sheet)
 *
 *
 * Copyright:
 *   Copyright 2013 Casey Patrick Driscoll (email : caseypatrickdriscoll@me.com)
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License, version 2, as 
 *   published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/ 

// The class for constucting the calendar
include 'class-patched-up-pixel-calendar.php';

class Patched_Up_Pixel_Calendar_Widget extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'patched_up_pixel_calendar',
      'Pixel Calendar',
      array( 'description' => __( 'A pixel calendar widget', 'text_domain' ) )
    );
  }

  public function widget( $args, $instance ) {
    // Important styling for the calendar widget
    wp_register_style( 'patchedUpPixelCalendarStylesheet', plugins_url('css/widget.css', __FILE__) );
    wp_enqueue_style( 'patchedUpPixelCalendarStylesheet' );

    $title = apply_filters( 'widget_title', $instance['title'] );
    // $styles are taken from the form and given to the Patched_Up_Pixel_Calendar later
    $style = [ 'color'      => $instance['color'],
               'hovercolor' => $instance['hovercolor'] ];

		update_option('patched_up_pixel_calendar_style', $style);

    echo $args['before_widget'];

    // If there is a title, print it out 
    if ( !empty($title) )
      echo $args['before_title'] . $title . $args['after_title'];

    // The first important bit of logic. 
    // If the 'patched_up_pixel_calendar' transient doesn't exist in the db, make a new one and save it
    // Note the calendar is instantiated with the styles from before.
    if ( !get_transient( 'patched_up_pixel_calendar' ) ) 
			update_patched_up_pixel_calendar_transient();

    // Either way, now is the time to print out the saved calendar
    echo get_transient( 'patched_up_pixel_calendar' );
    
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    // Grab the existing styling variables if they exist
    if ( isset($instance) ) extract($instance);

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
      <input  type="text"
              class="widefat"
              id="<?php echo $this->get_field_id('color'); ?>"
              name="<?php echo $this->get_field_name('color'); ?>"
              maxlength="6"
              value="<?php if ( isset($color) ) echo esc_attr($color); ?>" />
    </p>
    <p><?php // Hovercolor form. Again will be js color picker in the future ?>
      <label for="<?php echo $this->get_field_id('hovercolor');?>">Pixel Hover Color:</label> 
      <input  type="text"
              class="widefat"
              id="<?php echo $this->get_field_id('hovercolor'); ?>"
              name="<?php echo $this->get_field_name('hovercolor'); ?>"
              maxlength="6"
              value="<?php if ( isset($hovercolor) ) echo esc_attr($hovercolor); ?>" />
    </p>
		<h3>Preview</h3>
		<div class="widget_patched_up_pixel_calendar"> 
			<?php
    	  if ( !get_transient( 'patched_up_pixel_calendar' ) ) 
					update_patched_up_pixel_calendar_transient();

				echo get_transient( 'patched_up_pixel_calendar' );
			?>
		</div>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
   
		// Fields
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['color'] = strip_tags($new_instance['color']);
    $instance['hovercolor'] = strip_tags($new_instance['hovercolor']);

    $style = [ 'color'      => $instance['color'],
               'hovercolor' => $instance['hovercolor'] ];

		update_option('patched_up_pixel_calendar_style', $style);

    update_patched_up_pixel_calendar_transient();
  
    return $instance;
  }


}

function update_patched_up_pixel_calendar_transient($style) {
  set_transient( 'patched_up_pixel_calendar', 
								 new Patched_Up_Pixel_Calendar(get_option('patched_up_pixel_calendar_style') )
							 );
}
add_action( 'save_post', 'update_patched_up_pixel_calendar_transient' );
add_action( 'delete_post', 'update_patched_up_pixel_calendar_transient' );

// Style the preview grid in the widget admin
function register_patched_up_pixel_calendar_styles() {
  wp_register_style( 'patchedUpPixelCalendarStylesheet', plugins_url('css/widget.css', __FILE__) );
  wp_enqueue_style( 'patchedUpPixelCalendarStylesheet' );
} 
add_action( 'admin_footer', 'register_patched_up_pixel_calendar_styles' );

// Standard widget registration
function register_patched_up_pixel_calendar_widget() {
  register_widget( 'Patched_Up_Pixel_Calendar_Widget' );
}
add_action( 'widgets_init', 'register_patched_up_pixel_calendar_widget' );
