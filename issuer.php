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
      if (get_option("current_issue") == $term_id) { ?>
        <button class="btn btn-block btn-success issuer-disabled" disabled="disabled" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Active</button>
      <?php } else { ?>
        <button class="btn btn-block btn-primary issuer-active" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Make Active</button>
      <?php }
    }  
}  

add_filter('manage_edit-issue_columns', 'ST4_columns_head');  
add_filter('manage_issue_custom_column', 'ST4_columns_content_taxonomy', 10, 3);  

function issuer_make_endpoint() {
  // register a JSON endpoint for the root
  add_rewrite_endpoint("issuer", EP_ROOT);
}
add_action("init", "issuer_make_endpoint");
function issuer_add_queryvars( $query_vars ) {  
    $query_vars[] = 'issuer';  
    return $query_vars;  
}  
add_filter( 'query_vars', 'issuer_add_queryvars' );

function issuer_json_endpoint() {
  global $wp_query;
  if (!isset($wp_query->query_vars['issuer'])) {
    return;
  }

  $issue = $_POST["issuer"];
  update_option("current_issue", $issue);

  header("Content-Type: application/json");

  $response = Array( "response" => "success");
  echo json_encode($response);
  exit();
}
add_action( 'template_redirect', 'issuer_json_endpoint' );

function issuer_endpoints_activate() {
  issuer_make_endpoint();
  flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'issuer_endpoints_activate' );

function issuer_deendpoints_activate() {
  // flush rules on deactivate as well so they're not left hanging around uselessly
  flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'issuer_deendpoints_activate' );

function current_issue($query=array()) {
  if (get_option("current_issue") != 0) {
    return array_merge($query, array("tax_query" => array( array( 
      "taxonomy" => "issue",
      "terms" => get_option("current_issue"),
      "field" => "term_id")
    )));
  } else {
    if (empty($query)) {
      return "";
    } else {
      return $query;
    }
  }
}

function get_issue($query=array(), $issue_name=0, $issue_id=0) {
  if ($issue_name = 0) {
    // try get it from the query vars
    if (isset($wp_query->query_vars['issue'])) {
      $issue_name = $wp_qery->query_vars['issue'];
    } elseif (isset($_GET["issue"])) {
      // try get it from the post
      $issue_id = $_GET["issue"];
    } else {
      if (empty($query)) {
        return '';
      } else {
        return $query;
      }
        
    }
  }
  if (empty($issue_name)) {
    $issue_query = array("issue" => $issue_name);
  } else {
    $issue_query = array("tax_query" => array( array( 
      "taxonomy" => "issue",
      "terms" => $issue_id,
      "field" => "term_id")
    ));
  }
  if (!empty($query)) {
    return array_merge($query, $issue_query);
  } else {
    return $issue_query;
  }
}

function list_issues($limit=0, $orderby="term_id", $order="DESC") { 
  $args = array(
    'orderby'       => $orderby, 
    'order'         => $order,
    'number'        => (empty($limit) ? '' : $limit), 
  );
  $terms = get_terms("issue", $args); ?>
  <ul class="issues-list">
  <?php foreach ($terms as $term) { ?>
    <li class="issue-item"><a href='<?php echo '/issues/' . $term->slug ?>'
      title='View all posts in <?php echo $term->name ?>'><?php echo $term->name ?></a></li>
  <?php } ?>
  </ul>
  <?php
}
