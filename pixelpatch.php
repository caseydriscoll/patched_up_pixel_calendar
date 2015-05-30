<?php

/* Plugin Name: PixelPatch
 * Plugin URI: https://patch.works/plugin/pixelpatch
 * Description: A widget for displaying posts as a minimalist raster calendar of 'pixels' similar to Github's 'contribution calendar'
 * Version: 2.0.0
 * Date: 2015-05-29 21:56:55
 * Author: Casey Patrick Driscoll
 * Author URI: https://caseypatrickdriscoll.com
 *
 * Copyright:
 *   Copyright 2015 Casey Patrick Driscoll (email : casey@patch.works)
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
include 'class-pixelpatch-widget.php';
include 'class-pixelpatch-calendar.php';



class PixelPatch {


	function __construct() {

		add_action( 'save_post', array( $this, 'update_transient' ) );
		add_action( 'delete_post', array( $this, 'update_transient' ) );


		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles' ) );


		add_action( 'widgets_init', array( $this, 'register_widget' ) );

	}



	/**
	 * Everytime a post is saved or deleted, 
	 * render a new calendar and update the transient
	 *
	 * @author  caseypatrickdriscoll casey@patch.works
	 *
	 * @edited  2015-05-29 20:42:58
	 *
	 * @action  save_post
	 * @action  delete_post
	 * 
	 * @param  [type] $style [description]
	 * 
	 * @return [type]        [description]
	 */
	static public function update_transient( $style ) {

		$pixelpatch_calendar_style = get_option( 'pixelpatch_calendar_style' );

		set_transient( 
			'pixelpatch_calendar', 
			new PixelPatch_Calendar( $pixelpatch_calendar_style )
		);

	}



	// Style the preview grid in the widget admin
	// TODO: Only if on widgets page
	function register_styles() {
		wp_register_style( 'pixelpatch', plugins_url( 'css/widget.css', __FILE__ ) );
		wp_register_script( 'pixelpatch', plugins_url('js/script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );

		wp_enqueue_style( 'pixelpatch' );
	}




	// Standard widget registration
	function register_widget() {
		register_widget( 'PixelPatch_Widget' );
	}


}

new PixelPatch();