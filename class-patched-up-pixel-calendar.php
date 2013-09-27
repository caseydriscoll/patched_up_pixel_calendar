<?php

/* In This File:
 *    The calendar class for generating the html grid. Functions include:
 *      __construct()
 *      __toString()
 *      build_calendar()  <- Queries the db and constructs the entire calendar
 *      build_pixel()     <- Helper method that builds the individual day
 *      hex2rgb()         <- Helper method for converting six digit color hexes to rgb
 *      filter_where()    <- Filter for db query
 *
 * How It Works:
 *    The __construct() builds the calendar and assigns it to $calendar for later retrieval by __toString()
 *
 *      'posts'        <= an array of each post returned by WP_Query()
 *      'dayoftheyear' <= a number between 1 and 365 used for iteration while building the calendar
 *      'dayoftheweek' <= a number between 1 and 7 that represents the current day of the week
 *      'calendar'     <= the html output of the calendar
 *
 *    build_calendar() runs a WP_Query() for all posts from the last 365 days (as filtered by filter_where())
 *
 *    build_calendar() runs through each post of the query, 
 *      grabbing only the title while making three versions of the_date()
 *      Each 'post' in the array includes these:
 *
 *      'title'       <= The title
 *      'index_date'  <= A number between 1 and 365 representing the day of the year of the post 
 *      'slug_date'   <= The 'Ymd' format (20130927) of the date for the archive link
 *      'print_date'  <= The readable date format (September 27, 2013)
 *
 *    The 'index_date' then needs to be reformatted to the day of the year if the year started 365 days ago. 
 *    For example, a blog posted on 9/27/2013 would have the 'index_date' of 270, 
 *      as 9/27 is the 270th day of 2013.
 *    However the calendar is built by *looking back* a year (365 days), as opposed to starting on Jan 1. 
 *    Thus, if the current day the viewer is on the site is 9/28, 
 *      then the blog post of 9/27 should have an 'index_date' of 364, 
 *      being the 364th day if the year started 365 days ago. 
 *    On 9/29 the post will have an 'index_date' of 363, 362 on 9/30, etc.
 *    Each index date is refactored to represent this perspective shift
 *     and now has an index_date of the 'day of the year' had 'a year' started 365 days ago, not on Jan 1
 *
 *    Once the 'index_date' of the 'post' is refactored, it is added to the $this->posts array 
 *    Every post is fitlered this way and the original query results are discarded
 *
 *    Now we have an array of posts and it is time to construct the html.
 *    The array is reversed so that we may quickly pop() each post off as we traverse the array.
 *
 *    The html of the calendar looks roughly like this:
 *
 *      <ul class="patched_up_pixel_calendar">
 *        <li class="patched_up_pixel_calendar_week">
 *          <ul>
 *            <li class="patched_up_pixel_calendar_day">
 *              <a href="?m=['slug_date']">
 *                <span>
 *                  <strong>['post']['print_date']</strong>
 *                  <p>['post]['title']</p>
 *                  .
 *                  . (repeats for the number of posts this day)
 *                  .
 *                </span>
 *              </a>
 *            </li> <!-- end of day -->
 *            .
 *            . (times 7 days per week)
 *            .
 *          </ul>
 *        </li> <!-- end of week -->
 *        .
 *        . (times 53 weeks per year)
 *        .
 *      </ul>
*/


class Patched_Up_Pixel_Calendar {
  private $calendar = '', $posts, $dayoftheyear, $dayoftheweek;

  public function __construct($style) {
    $this->posts = array();
    $this->dayoftheyear = 1;
    $this->dayoftheweek = date('w') + 1;
    $this->calendar = $this->build_calendar($style);
  }

  public function __toString() {
    return $this->calendar;
  }
  
  private function build_calendar($style) {
    extract($style);

    /* Query for the calendar */
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

    while ( $calendar_query->have_posts() ) {
      $calendar_query->the_post();

      $post = [];
      $post['title'] = get_the_title();
      $post['index_date'] = get_the_date('z');
      $post['slug_date']  = get_the_date('Ymd');
      $post['print_date'] = get_the_date();

      // index current post date if a year started 365 days ago
      if ( $post['index_date'] > date('z') )
        $post['index_date'] = $post['index_date'] - date('z');
      else
        $post['index_date'] = 365 - date('z') + $post['index_date'];

      array_push($this->posts, $post);
    }
    $this->posts = array_reverse($this->posts);



    /* Construct the calendar html */
    // Quick contact information about the plugin
    $calendar =  '<!-- Patched Up Pixel Calendar by Casey Patrick Driscoll of Patched Up Creative 2013 -->';
    $calendar .= '<!--   caseypatrickdriscoll.com  ---  patchedupcreative.com/plugins/pixel-calendar   -->';

    // A list of written months across the top (to be added later)
    $calendar .= '<ul class="patched_up_pixel_calendar_months">';
      for ( $i = 0 ; $i < 12 ; $i++ )
        $calendar .= '<li>' . date('M', mktime(0, 0, 0, date('m')+$i+2, 0, 0)) . '</li>';
    $calendar .= '</ul>';

    // ul calendar of pixels comprised of vertical lis of weeks built of more ul of days
    $calendar .= '<ul id="patched_up_pixel_calendar">';


    // There are three cases to consider when constructing each week:
    //    Front Case: The first week (with blank initial days)
    //    Back Case: The last week (with blank ending days)
    //    Middle Casey: Every other full week in the middle
    for ( $week = 0 ; $week < 53 ; $week++ ) { // for 53 partial weeks
      $calendar .= '<li class="patched_up_pixel_calendar_week">';

        $calendar .= '<ul>';

        if ( $week == 0 ) { // Front Case
          // Fill in the initial blank days
          $blankdays = 7 - (365 - $this->dayoftheweek - (51 * 7));
          for ( $i = 0 ; $i < $blankdays ; $i++ ) 
            $calendar .= '<li class="patched_up_pixel_calendar_blankday"></li>';

          // Fill in the rest of the 7 days
          for ( $day = $blankdays ; $day < 7 ; $day++ )
            $calendar .= $this->build_pixel();

        } elseif ( $week == 52 ) { // Back Case
          // Fill in only until you run out of days
          for ( $day = 0 ; $day < $this->dayoftheweek ; $day++ )
            $calendar .= $this->build_pixel();
          
        } else { // Middle Case
          // Fill in all 7 days
          for ( $day = 0 ; $day < 7 ; $day++ ) 
            $calendar .= $this->build_pixel();
        }   

        $calendar .= '</ul>';
      $calendar .= '</li>';
    }
    $calendar .= '</ul>';
    // End Construct Calendar



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

    return $calendar;
  }

  private function build_pixel() {
    $current_post = array_pop($this->posts);
    $pixel = '<li class="patched_up_pixel_calendar_day';
    $numberofposts = 0;
    $tooltip = '';

    if ( $current_post['index_date'] == $this->dayoftheyear ) {
      $tooltip .= '<a href="/?m=' . $current_post['slug_date'] . '"></a><span>';
      $tooltip .= '<strong>' . $current_post['print_date'] . '</strong>';

      while ( $current_post['index_date'] == $this->dayoftheyear ) {
        $tooltip .= '<p>' . $current_post['title'] . '</p>';
        $current_post = array_pop($this->posts);

        $numberofposts++;
      }

      $tooltip .= '</span>';
    } 

    // Since there is no peek we have to push on the currently used post to restore it.
    array_push($this->posts, $current_post);
   

    if ( $numberofposts == 0 )
      $pixel .= '">';
    elseif ( $numberofposts == 1 )
      $pixel .= ' tooltip onepost">';
    elseif ( $numberofposts == 2 )
      $pixel .= ' tooltip twoposts">'; 
    elseif ( $numberofposts >= 3 )
      $pixel .= ' tooltip manyposts">'; 


    $pixel .= $tooltip . '</li>';

    $this->dayoftheyear++;

    return $pixel;
  }

  // Thanks to http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
  private function hex2rgb($hex) {
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
