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

  public function widget( $args, $instance ) {
    wp_register_style( 'patchedUpPixelCalendarStylesheet', plugins_url('patched_up_pixel_calendar_style.css', __FILE__) );
    wp_enqueue_style( 'patchedUpPixelCalendarStylesheet' );

    $title = apply_filters( 'widget_title', $instance['title'] );

    echo $args['before_widget'];

    if ( !empty($title) )
      echo $args['before_title'] . $title . $args['after_title'];

    function filter_where( $where = '' ) {
      $today= date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d") + 1,   date("Y")) );
      $yesteryear = date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d"),   date("Y") - 1) );
      $where .= " AND post_date > '" . $yesteryear . "' AND post_date <= '" . $today . "'";
      return $where;
    }

    $query_string = array( 
      'post_type' => 'post', 
      'posts_per_page' => '-1',
      'post_status' => 'publish',
      'order_by' => 'date',
      'order' => 'ASC' 
    );
    add_filter( 'posts_where', 'filter_where' );
    $calendar_query = new WP_Query( $query_string );
    remove_filter( 'posts_where', 'filter_where' );

    //while ( $calendar_query->have_posts() ) {
      //$calendar_query->the_post();
      //if ( get_the_date('z') > date('z') )
        //echo get_the_title() . ' ' . get_the_date('z') . ' - ' . date('z') . ' = ' .  (get_the_date('z') - date('z')) . '<br />';
      //else
        //echo get_the_title() . ' ' . get_the_date('z') . ' + ' . date('z') . ' = ' .  (365 - date('z') + get_the_date('z')) . '<br />';
    //}

    // ul calendar of pixels
    $calendar = '<ul id="patched_up_pixel_calendar">';

    $calendar_query->the_post();
    $postdayoftheyear = (get_the_date('z') - date('z')); // an index for the current post date if a year started 365 days ago

    $dayoftheyear = 1;
    $dayoftheweek = date('w') + 1;

    $numberofposts = 0;

    for ( $week = 0 ; $week < 53 ; $week++ ) { // for 53 partial weeks
      $calendar .= '<li class="patched_up_pixel_calendar_week">';

        $calendar .= '<ul>';  

        if ( $week == 0 ) { // Front Case
          // empty spots equals 7 - (365 - days in last week - 51 solid weeks * 7 days)
          $blankdays = 7 - (365 - $dayoftheweek - (51 * 7));
          
          for ( $i = 0 ; $i < $blankdays ; $i++ ) {
            $calendar .= '<li class="patched_up_pixel_calendar_blankday"></li>';
          }

          for ( $day = $blankdays ; $day < 7 ; $day++ ) {
            $calendar .= '<li title="';
            $numberofposts = 0;
            while ( $postdayoftheyear == $dayoftheyear ) {
              $calendar .= get_the_title();
              $calendar_query->the_post();
              if ( get_the_date('z') > date('z') )
                $postdayoftheyear = get_the_date('z') - date('z');
              else
                $postdayoftheyear = 365 - date('z') + get_the_date('z');
              $numberofposts++;
            }
            $calendar .= '" class="patched_up_pixel_calendar_day'; 
            if ( $numberofposts > 0 )
              $calendar .= ' someposts'; 

            $calendar .= '"></li>';
            $dayoftheyear++;
          }
        } elseif ( $week == 52 ) { // Back Case
          for ( $day = 0 ; $day < $dayoftheweek ; $day++ ) {
            $calendar .= '<li title="';
            $numberofposts = 0;
            while ( $postdayoftheyear == $dayoftheyear ) {
              $calendar .= get_the_title();
              $calendar_query->the_post();
              if ( get_the_date('z') > date('z') )
                $postdayoftheyear = get_the_date('z') - date('z');
              else
                $postdayoftheyear = 365 - date('z') + get_the_date('z');
              $numberofposts++;
            }
            $calendar .= '" class="patched_up_pixel_calendar_day'; 
            if ( $numberofposts > 0 )
              $calendar .= ' someposts'; 

            $calendar .= '"></li>';
            $dayoftheyear++;
          }
        } else { // Middle Case
          for ( $day = 0 ; $day < 7 ; $day++ ) {
            $calendar .= '<li title="';
            $numberofposts = 0;
            while ( $postdayoftheyear == $dayoftheyear ) {
              //$calendar .= get_the_title();
              $calendar_query->the_post();
              if ( get_the_date('z') > date('z') )
                $postdayoftheyear = get_the_date('z') - date('z');
              else
                $postdayoftheyear = 365 - date('z') + get_the_date('z');
              $numberofposts++;
            }
            $calendar .= '" class="patched_up_pixel_calendar_day'; 
            if ( $numberofposts > 0 )
              $calendar .= ' someposts'; 

            $calendar .= '"></li>';
            $dayoftheyear++;
          }
        }   

        $calendar .= '</ul>';
      
      $calendar .= '</li>';
    }
    
    $calendar .= '</ul>';

    //wp_cache_set( 'patched_up_pixel_calendar', $calendar );
    //$calendar = wp_cache_get( 'patched_up_pixel_calendar' );

    echo $calendar;

    echo '<br style="clear:both;" />';

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
