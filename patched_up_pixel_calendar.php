<?php

/* Plugin Name: Patched Up Pixel Calendar
 * Plugin URI: http://patchedupcreative.com/plugins/pixel-calendar
 * Description: A widget for displaying a raster calendar similar to Github's "contribution calendar"
 * Author: Casey Patrick Driscoll
 * Author URI: http://caseypatrickdriscoll.com
*/ 

class Patched_Up_Pixel_Calendar extends WP_Widget {
  public function __construct() {
    parent::__construct(
      'patched_up_pixel_calendar',
      'Pixel Calendar',
      array( 'description' => __( 'A pixel calendar widget', 'text_domain' ) )
    );
  }

  public static function filter_where( $where = '' ) {
    $today= date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d") + 1,   date("Y")) );
    $yesteryear = date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d"),   date("Y") - 1) );
    $where .= " AND post_date > '" . $yesteryear . "' AND post_date <= '" . $today . "'";
    return $where;
  }

  public function build_pixel($calendar_info) {
    $calendar_info['pixel'] = '<li title="';
    $numberofposts = 0;
    $tooltip = '';

    if ( $calendar_info['current_post']['date'] == $calendar_info['dayoftheyear'] ) {
      $tooltip .= '<a href="?m=' . $calendar_info['current_post']['day'] . '"></a><span>';

      while ( $calendar_info['current_post']['date'] == $calendar_info['dayoftheyear'] ) {
        $tooltip .= '<p>' . $calendar_info['current_post']['title'] . '</p>';
        $calendar_info['current_post'] = array_pop($calendar_info['posts']);

        $numberofposts++;
      }

      $tooltip .= '</span>';
    }

    $calendar_info['pixel'] .= '" class="patched_up_pixel_calendar_day'; 

    if ( $numberofposts == 0 )
      $calendar_info['pixel'] .= '">';
    elseif ( $numberofposts == 1 )
      $calendar_info['pixel'] .= ' tooltip onepost">';
    elseif ( $numberofposts == 2 )
      $calendar_info['pixel'] .= ' tooltip twoposts">'; 
    elseif ( $numberofposts >= 3 )
      $calendar_info['pixel'] .= ' tooltip manyposts">'; 


    $calendar_info['pixel'] .= $tooltip . '</li>';

    $calendar_info['dayoftheyear']++;

    return $calendar_info;
  }

  public function build_calendar() {
    $calendar_info = array(); // A list of passable info to build_pixel()
    $query_string = array( 
      'post_type' => 'post', 
      'posts_per_page' => '-1',
      'post_status' => 'publish',
      'order_by' => 'date',
      'order' => 'ASC' 
    );
    add_filter( 'posts_where', array('Patched_Up_Pixel_Calendar', 'filter_where' ));
    $calendar_query = new WP_Query( $query_string );
    remove_filter( 'posts_where', 'filter_where' );

    $calendar_info['posts'] = array();
    $calendar_info['dayoftheyear'] = 1;
    $calendar_info['dayoftheweek'] = date('w') + 1;
    $calendar_info['pixel'] = '';

    while ( $calendar_query->have_posts() ) {
      $calendar_query->the_post();

      $post = [];
      $post['title'] = get_the_title();
      $post['day']   = get_the_date('Ymd');
      $post['date']  = get_the_date('z');

      // index current post date if a year started 365 days ago
      if ( $post['date'] > date('z') )
        $post['date'] = $post['date'] - date('z');
      else
        $post['date'] = 365 - date('z') + $post['date'];

      array_push($calendar_info['posts'], $post);
    }
    $calendar_info['posts'] = array_reverse($calendar_info['posts']);
    $calendar_info['current_post'] = array_pop($calendar_info['posts']);

    // ul calendar of pixels comprised of vertical lis of weeks built of more ul of days
    $calendar = '<ul id="patched_up_pixel_calendar">';

    for ( $week = 0 ; $week < 53 ; $week++ ) { // for 53 partial weeks
      $calendar .= '<li class="patched_up_pixel_calendar_week">';

        $calendar .= '<ul>';  

        if ( $week == 0 ) { // Front Case
          $blankdays = 7 - (365 - $calendar_info['dayoftheweek'] - (51 * 7));
          
          for ( $i = 0 ; $i < $blankdays ; $i++ ) $calendar .= '<li class="patched_up_pixel_calendar_blankday"></li>';

          for ( $day = $blankdays ; $day < 7 ; $day++ ) {
            $calendar_info = $this->build_pixel($calendar_info);
            $calendar .= $calendar_info['pixel'];
          }
        
        } elseif ( $week == 52 ) { // Back Case

          for ( $day = 0 ; $day < $calendar_info['dayoftheweek'] ; $day++ ) {
            $calendar_info = $this->build_pixel($calendar_info);
            $calendar .= $calendar_info['pixel'];
          }
        
        } else { // Middle Case

          for ( $day = 0 ; $day < 7 ; $day++ ) {
            $calendar_info = $this->build_pixel($calendar_info);
            $calendar .= $calendar_info['pixel'];
          }

        }   

        $calendar .= '</ul>';
      
      $calendar .= '<br style="clear:both;" />';

      $calendar .= '</li>';
    }
    
    $calendar .= '</ul>';

    //wp_cache_set( 'patched_up_pixel_calendar', $calendar );
    //$calendar = wp_cache_get( 'patched_up_pixel_calendar' );


    return $calendar;
  }

  public function widget( $args, $instance ) {
    wp_register_style( 'patchedUpPixelCalendarStylesheet', plugins_url('patched_up_pixel_calendar_style.css', __FILE__) );
    wp_enqueue_style( 'patchedUpPixelCalendarStylesheet' );

    wp_enqueue_script( 'patchedUpPixelCalendarScript', plugins_url('patched_up_pixel_calendar_script.js', __FILE__), array('jquery') );

    $title = apply_filters( 'widget_title', $instance['title'] );

    echo $args['before_widget'];

    if ( !empty($title) )
      echo $args['before_title'] . $title . $args['after_title'];


    $calendar = $this->build_calendar();

    echo $calendar;

    echo $args['after_widget'];
  }

  public function form( $instance ) {

  }

  public function update( $new_instance, $old_instance ) {

  }


}

function register_patched_up_pixel_calendar_widget() {
  register_widget( 'Patched_Up_Pixel_Calendar' );
}
add_action( 'widgets_init', 'register_patched_up_pixel_calendar_widget' );
