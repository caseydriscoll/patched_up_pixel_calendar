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
 *    == 1.0 QUERY FOR THE CALENDAR ==
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
 *    == 2.0 CONSTRUCT THE CALENDAR HTML ==
 *
 *    Now we have an array of posts and it is time to construct the html.
 *    The array is reversed so that we may quickly pop() each post off as we traverse the array.
 *    Cycle through all the weeks and build_pixel() for every day
 *
 *    The html of the calendar looks roughly like this:
 *
 *      <ul class="patched_up_pixel_calendar">
 *        <li class="patched_up_pixel_calendar_week">
 *          <ul>
 *            <li class="patched_up_pixel_calendar_day">
 *              <a href="?m=['slug_date']">
 *                <span> <- tooltip that is activated on hover
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
 *
 *
 *     == 3.0 STYLING INFORMATION ==
 *
 *     The color of each pixel is controlled by the opacity (the bg color controlled by the user)
 *
 *     For now there are three levels of intensity, although that might change in the future.
 *
 *     I would like to just dynamically change the color of the background,
 *      but from what I can tell with rgba opacity the whole line needs to be updated.
 *
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

    /* 1.0 QUERY FOR THE CALENDAR */
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
      $post['index_date'] = get_the_date('z');    // numbered day of the year 1 - 365 
      $post['slug_date']  = get_the_date('Ymd');  // WP slug format for day archive link (20130927)
      $post['print_date'] = get_the_date();       // What the user actually reads

      // index current post date if a year started 365 days ago (clever maths)
      if ( $post['index_date'] > date('z') ) // if it happened after this day last year
        $post['index_date'] = $post['index_date'] - date('z'); // it's simply the remainder on the year
      else // otherwise it's before the current day, and more hairy. 365 - date is last number of last year
        $post['index_date'] = 365 - date('z') + $post['index_date']; // and then add the lower date number

      array_push($this->posts, $post); // push it to the array
    }
    $this->posts = array_reverse($this->posts); // reversed for quick access to the bottom of the stack
    /* End 1.0 QUERY FOR THE CALENDAR */



    /* 2.0 CONSTRUCT THE CALENDAR HTML */
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
    // End 2.0 CONSTRUCT THE CALENDAR HTML */



    /* 3.0 STYLING INFORMATION */
    // Would style in an external sheet, just not sure how to do that dynmically in WP Plugins quite yet
    if ( !isset( $color ) ) 
      $color = hex2rgb( '000000' ); // Set the default color to black
    else
      $color = $this->hex2rgb($color);
    
    $hovercolor = $this->hex2rgb($hovercolor);

    // color intesifies opacity with every added post. AFAIK can't control bg opacity by itself.
    $calendar .= '
      <style type="text/css">
        .patched_up_pixel_calendar_day                 { background-color: rgba(' . $color . ',0.1); }  
        .patched_up_pixel_calendar_day.onepost         { background-color: rgba(' . $color . ',0.4); }
        .patched_up_pixel_calendar_day.twoposts        { background-color: rgba(' . $color . ',0.7); }
        .patched_up_pixel_calendar_day.manyposts       { background-color: rgba(' . $color . ',1.0); }
        .patched_up_pixel_calendar_day.tooltip a:hover { background-color: rgba(' . $hovercolor . ',1.0); }
      </style>
    ';
    /* End 3.0 STYLING INFORMATION */

    return $calendar;
  }

  // This function builds the 'pixel' for the calendar, returning a <li> of posts
  private function build_pixel() {
    $current_post = array_pop($this->posts); // Peeking for now, will add it back on it a bit
    $pixel = '<li class="patched_up_pixel_calendar_day'; // leave class open to add more in a bit
    $numberofposts = 0; // used for deciding the color intensity of the pixel

    // Skip if this day should be blank, otherwise fill it with a tooltip that activates on hover
    $tooltip = '';
    if ( $current_post['index_date'] == $this->dayoftheyear ) {
      $tooltip .= '<a href="/?m=' . $current_post['slug_date'] . '"></a><span>';
      $tooltip .= '<strong>' . $current_post['print_date'] . '</strong>'; // add the date only once

      while ( $current_post['index_date'] == $this->dayoftheyear ) { // but then add every pertinent title
        $tooltip .= '<p>' . $current_post['title'] . '</p>';
        $current_post = array_pop($this->posts);

        $numberofposts++; // used later for adding color to the pixel
      }

      $tooltip .= '</span>'; // finish the tooltip
    } 

    // Since there is no peek we have to push on the currently used post to restore the array.
    array_push($this->posts, $current_post);
   
    // Add a class to show how many posts there are to identify the opacity in styling
    if ( $numberofposts == 0 )
      $pixel .= '">';
    elseif ( $numberofposts == 1 )
      $pixel .= ' tooltip onepost">';
    elseif ( $numberofposts == 2 )
      $pixel .= ' tooltip twoposts">'; 
    elseif ( $numberofposts >= 3 )
      $pixel .= ' tooltip manyposts">'; 

    $pixel .= $tooltip . '</li>';

    $this->dayoftheyear++; // prepare for the next day

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

  // Quick filter for going back a year in posts
  static function filter_where( $where = '' ) {
    $today = date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d") + 1,   date("Y")) );
    $yesteryear = date("Y-m-d", mktime(0, 0, 0, date("m"),   date("d"),   date("Y") - 1) );
    $where .= " AND post_date > '" . $yesteryear . "' AND post_date <= '" . $today . "'";
    return $where;
  }

}
?>
