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
    'query_var'         => true
  );

  register_taxonomy( 'issue', array( 'post', 'page' ), $args );
}
add_action("init", "add_issue_taxonomy");
add_filter('post_link', 'issue_permalink', 10, 3);
add_filter('post_type_link', 'issue_permalink', 10, 3);
 
function issue_permalink($permalink, $post_id, $leavename) {
    if (strpos($permalink, '%issue%') === FALSE) return $permalink;
     
         //Get post
        $post = get_post($post_id);
        if (!$post) return $permalink;
 
         //Get taxonomy terms
        $terms = wp_get_object_terms($post->ID, 'issue');   
        if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0])) $taxonomy_slug = $terms[0]->slug;
        else $taxonomy_slug = 'other';
 
    return str_replace('%issue%', $taxonomy_slug, $permalink);
}  

function issuer_setup() {
  add_option( "current_issue", 0);
  add_option( "exclude_issues", array());
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
   //Register CSS
    wp_register_style(
      'issuer-bootstrap-css',
      plugins_url( '/css/bootstrap.min.css', __FILE__ )
    );
    wp_enqueue_style( 'issuer-bootstrap-css' );
}
add_action( 'admin_enqueue_scripts', 'issuer_add_admin_scripts' );


function issuer_edit_head($defaults) {  
    $defaults['current']  = 'Current';  
    $defaults['issuer-hide']  = 'Hide';  
  
  
    return $defaults;  
}  

function issuer_edit_column($c, $column_name, $term_id) {  
    if ($column_name == 'current') {  
      if (get_option("current_issue") == $term_id) { ?>
        <button class="btn btn-block btn-success issuer current-disabled" disabled="disabled" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Active</button>
      <?php } else { ?>
        <button class="btn btn-block btn-primary issuer current-active" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Make Active</button>
      <?php }
    } elseif ($column_name == 'issuer-hide') {
      if (in_array($term_id, get_option("exclude_issues"))) { ?>
        <button class="btn btn-block btn-danger issuer exclude-disabled" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Hidden</button>
      <?php } else { ?>
        <button class="btn btn-block btn-warning issuer exclude-active" data-tax_id=<?php echo $term_id ?> data-root=<?php echo site_url(); ?>>Hide</button>
      <?php }
    }
}  

add_filter('manage_edit-issue_columns', 'issuer_edit_head');  
add_filter('manage_issue_custom_column', 'issuer_edit_column', 10, 3);  

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

  $response = Array( "response" => "success");

  if (isset($_POST["issuer_active"])) {
    $issue = $_POST["issuer_active"];
    update_option("current_issue", $issue);
  } elseif  ($_POST["issuer_exclude"]) {
    $issue = $_POST["issuer_exclude"];
    $exclude = get_option("exclude_issues");
    if (!in_array($issue, $exclude)) {
      $exclude[] = $issue;
      update_option("exclude_issues", $exclude);
    } else {
      $response = Array( "response" => "failure");
    }
  } elseif  ($_POST["issuer_include"]) {
    $issue = $_POST["issuer_include"];
    $exclude = get_option("exclude_issues");
    if (($pos = array_search($issue, $exclude)) !== false) {
      $exclude = get_option("exclude_issues");
      unset($exclude[$pos]);
      update_option("exclude_issues", $exclude);
    } else {
      $response = Array( "response" => "failure");
    }
  } else {
    $response = Array( "response" => "failure");
  }

  header("Content-Type: application/json");

  echo json_encode($response);
  exit();
}
add_action( 'template_redirect', 'issuer_json_endpoint' );


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

function active_issue($query=array()) {
  $issue = get_query_var('issue'); 
  if (empty($issue) ) {
    $issue = get_option("current_issue");
    if (!empty($issue)) {
      $issue = get_term_by("id", $issue, "issue")->slug;
    }
  }
  if (!empty($issue)) {
    return array_merge($query, array("tax_query" => array( array( 
      "taxonomy" => "issue",
      "terms" => $issue,
      "field" => "slug")
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
      $issue_id = get_option("current_issue");
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
    'exclude'       => get_option("exclude_issues")
  );
  $terms = get_terms("issue", $args); ?>
  <ul class="issues-list">
  <?php foreach ($terms as $term) { ?>
    <li class="issue-item"><a href='<?php echo site_url() . '/' . $term->slug ?>'
      title='View all posts in <?php echo $term->name ?>'><?php echo $term->name ?></a></li>
  <?php } ?>
  </ul>
  <?php
}

function issuer_endpoints_activate() {
  global $wp_rewrite;
  issuer_make_endpoint();
  add_issue_taxonomy();
  $wp_rewrite -> flush_rules();
}
register_activation_hook( __FILE__, 'issuer_endpoints_activate' );

function issuer_deendpoints_activate() {
  // flush rules on deactivate as well so they're not left hanging around uselessly
  global $wp_rewrite;
  $wp_rewrite -> flush_rules();
}
register_deactivation_hook( __FILE__, 'issuer_deendpoints_activate' );
