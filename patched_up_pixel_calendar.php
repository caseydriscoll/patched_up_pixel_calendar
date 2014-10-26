<?php

class Patched_Up_Pixel_Calendar {

  public function __construct($style) {
    $this->build_calendar($style);
  }
  
  function build_calendar($style) {
    extract($style);

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
      $post['print_date'] = get_the_date();

      // index current post date if a year started 365 days ago
      if ( $post['date'] > date('z') )
        $post['date'] = $post['date'] - date('z');
      else
        $post['date'] = 365 - date('z') + $post['date'];

      array_push($calendar_info['posts'], $post);
    }
    $calendar_info['posts'] = array_reverse($calendar_info['posts']);
    $calendar_info['current_post'] = array_pop($calendar_info['posts']);

    $calendar =  '<!-- Patched Up Pixel Calendar by Casey Patrick Driscoll of Patched Up Creative 2013 -->';
    $calendar .= '<!--   caseypatrickdriscoll.com  ---  patchedupcreative.com/plugins/pixel-calendar   -->';

    // A list of the months across the top
    $calendar .= '<ul class="patched_up_pixel_calendar_months">';

    for ( $i = 0 ; $i < 12 ; $i++ )
      $calendar .= '<li>' . date('M', mktime(0, 0, 0, date('m')+$i+2, 0, 0)) . '</li>';

    $calendar .= '</ul>';

    // ul calendar of pixels comprised of vertical lis of weeks built of more ul of days
    $calendar .= '<ul id="patched_up_pixel_calendar">';

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
      
      $calendar .= '</li>';
    }
    
    $calendar .= '</ul>';

    if ( !isset( $color ) ) 
      $color = hex2rgb( '000000' );
    else
      $color = $this->hex2rgb($color);
    
    $hovercolor = $this->hex2rgb($hovercolor);


    $calendar .= '
      <style type="text/css">
        .patched_up_pixel_calendar_day                 { background-color: rgba(' . $color . ',0.1); }  
        .patched_up_pixel_calendar_day.onepost         { background-color: rgba(' . $color . ',0.4); }
        .patched_up_pixel_calendar_day.twoposts        { background-color: rgba(' . $color . ',0.7); }
        .patched_up_pixel_calendar_day.manyposts       { background-color: rgba(' . $color . ',1.0); }
        .patched_up_pixel_calendar_day.tooltip a:hover { background-color: rgba(' . $hovercolor . ',1.0); }
      </style>
    ';

    set_transient( 'patched_up_pixel_calendar', $calendar, DAY_IN_SECONDS );
  }

  function build_pixel($calendar_info) {
    $calendar_info['pixel'] = '<li title="';
    $numberofposts = 0;
    $tooltip = '';

    if ( $calendar_info['current_post']['date'] == $calendar_info['dayoftheyear'] ) {
      $tooltip .= '<a href="/?m=' . $calendar_info['current_post']['day'] . '"></a><span>';
      $tooltip .= '<strong>' . $calendar_info['current_post']['print_date'] . '</strong>';

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

  // Thanks to http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
  function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);

    if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
    }
    $rgb = array($r, $g, $b);
    return implode(",", $rgb); // returns the rgb values separated by commas
  }

  static function filter_where( $where = '' ) {
    $today= date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d") + 1,   date("Y")) );
    $yesteryear = date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d"),   date("Y") - 1) );
    $where .= " AND post_date > '" . $yesteryear . "' AND post_date <= '" . $today . "'";
    return $where;
  }

}
?>
