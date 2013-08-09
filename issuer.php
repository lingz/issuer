<?php
/*
 * Plugin Name: Issuer
 * Plugin URI: http://google.com
 * Description: Helpers for creating Issues (a custom taxonomy) for posts to be filed under
 * Version: 1.0
 * Author: Lingliang Zhang
 * Author URI: http://lingliang.me
 * 
    Copyright 2013
    Lingliang Zhang
    (lingliangz@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301
    USA
  */

function add_issue_taxonomy() {
  $labels = array(
    'name'              => _x( 'Issues', 'taxonomy general name' ),
    'singular_name'     => _x( 'Issue', 'taxonomy singular name' ),
    'search_items'      => __( 'Search Issues' ),
    'all_items'         => __( 'All Issues' ),
    'edit_item'         => __( 'Edit Issue' ),
    'update_item'       => __( 'Update Issue' ),
    'add_new_item'      => __( 'Add New Issue' ),
    'new_item_name'     => __( 'New Issue Name' ),
    'menu_name'         => __( 'Issue' ),
  );
  $args = array(
    'hierarchical'      => false,
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => true,
    'query_var'         => true,
    'rewrite'           => array( 'slug' => 'issue' ),
  );

  register_taxonomy( 'issue', array( 'post', 'page' ), $args );
}
add_action("init", "add_issue_taxonomy");

function issuer_setup() {
  add_option( "current_issue", 0);
}
add_action("init", "issuer_setup");

function issuer_add_admin_scripts() {
		global $pagenow;
		// Only add JS and CSS on the edit-tags page
		if ( $pagenow == 'edit-tags.php' ) {
			wp_register_script(
				'issuer-js',
				plugins_url( '/js/issuer.js', __FILE__ ),
				array( 'jquery' )
			);
			wp_enqueue_script( 'issuer-js' );
    }
    // Register CSS
    wp_register_style(
      'issuer-bootstrap-css',
      plugins_url( '/css/bootstrap.min.css', __FILE__ )
    );
    wp_enqueue_style( 'issuer-bootstrap-css' );
}
add_action( 'admin_enqueue_scripts', 'issuer_add_admin_scripts' );


function ST4_columns_head($defaults) {  
    $defaults['current']  = 'Current';  
  
    /* ADD ANOTHER COLUMN (OPTIONAL) */  
    // $defaults['second_column'] = 'Second Column';  
  
    /* REMOVE DEFAULT CATEGORY COLUMN (OPTIONAL) */  
    // unset($defaults['categories']);  
  
    /* TO GET DEFAULTS COLUMN NAMES: */  
    // print_r($defaults);  
  
    return $defaults;  
}  

// TAXONOMIES: CATEGORIES (POSTS AND LINKS), TAGS AND CUSTOM TAXONOMIES  
function ST4_columns_content_taxonomy($c, $column_name, $term_id) {  
    if ($column_name == 'current') {  
      echo $term_id;
    }  
}  

add_filter('manage_edit-issue_columns', 'ST4_columns_head');  
add_filter('manage_issue_custom_column', 'ST4_columns_content_taxonomy', 10, 3);  
