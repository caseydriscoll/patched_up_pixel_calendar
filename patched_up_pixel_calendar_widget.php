<?php

/* Plugin Name: Patched Up Pixel Calendar
 * Plugin URI: http://patchedupcreative.com/plugins/pixel-calendar
 * Description: A widget for displaying a minimalist raster calendar of 'pixels' similar to Github's 'contribution calendar'
 * Version: 1.0.0
 * Author: Casey Patrick Driscoll
 * Author URI: http://caseypatrickdriscoll.com
*/ 

include 'patched_up_pixel_calendar.php';

class Patched_Up_Pixel_Calendar_Widget extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'patched_up_pixel_calendar',
      'Pixel Calendar',
      array( 'description' => __( 'A pixel calendar widget', 'text_domain' ) )
    );
  }

  public function widget( $args, $instance ) {
    wp_register_style( 'patchedUpPixelCalendarStylesheet', plugins_url('css/widget.css', __FILE__) );
    wp_enqueue_style( 'patchedUpPixelCalendarStylesheet' );

    $title = apply_filters( 'widget_title', $instance['title'] );
    $style = [ 'color'      => $instance['color'],
               'hovercolor' => $instance['hovercolor'] ];

    echo $args['before_widget'];

    if ( !empty($title) )
      echo $args['before_title'] . $title . $args['after_title'];

    $calendar = get_transient( 'patched_up_pixel_calendar' );

    if ( !$calendar )
      new Patched_Up_Pixel_Calendar($style);

    $calendar = get_transient( 'patched_up_pixel_calendar' );
    
    echo $calendar;

    echo $args['after_widget'];
  }

  public function form( $instance ) {
    if ( isset($instance) ) extract($instance);

  ?>
    <p>
      <label for="<?php echo $this->get_field_id('title');?>">Title:</label> 
      <input  type="text"
              class="widefat"
              id="<?php echo $this->get_field_id('title'); ?>"
              name="<?php echo $this->get_field_name('title'); ?>"
              value="<?php if ( isset($title) ) echo esc_attr($title); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('color');?>">Pixel Base Color:</label> 
      <input  type="text"
              class="widefat"
              id="<?php echo $this->get_field_id('color'); ?>"
              name="<?php echo $this->get_field_name('color'); ?>"
              maxlength="6"
              value="<?php if ( isset($color) ) echo esc_attr($color); ?>" />
    </p>
    <p>
      <label for="<?php echo $this->get_field_id('hovercolor');?>">Pixel Hover Color:</label> 
      <input  type="text"
              class="widefat"
              id="<?php echo $this->get_field_id('hovercolor'); ?>"
              name="<?php echo $this->get_field_name('hovercolor'); ?>"
              maxlength="6"
              value="<?php if ( isset($hovercolor) ) echo esc_attr($hovercolor); ?>" />
    </p>
    <?php

  }

  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    // Fields
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['color'] = strip_tags($new_instance['color']);
    $instance['hovercolor'] = strip_tags($new_instance['hovercolor']);

    delete_transient('patched_up_pixel_calendar');
  
    return $instance;
  }


}

function delete_transient_calendar_on_post_save() {
  delete_transient('patched_up_pixel_calendar');
}
add_action( 'save_post', 'delete_transient_calendar_on_post_save' );
add_action( 'delete_post', 'delete_transient_calendar_on_post_save' );

function register_patched_up_pixel_calendar_widget() {
  register_widget( 'Patched_Up_Pixel_Calendar_Widget' );
}
add_action( 'widgets_init', 'register_patched_up_pixel_calendar_widget' );
