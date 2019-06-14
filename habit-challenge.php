<?php
/**
 * @package habit-challenge
 * @version 1.2
 */
/*
Plugin Name: Habit Challenge
Plugin URI: http://team-x.ca/habit-challenge/
Description: Plugin for the Habit Challenge.
Version: 1.2
Author: Laszlo
Author URI: http://team-x.ca/
License: GPLv2 or later
Text Domain: habit-challenge
*/

/*
License... This program blah blah...
*/

defined("ABSPATH") or die();

class HabitChallengePlugin
{
  function __construct()
  {
    //echo "__construct";
    add_action("init", array($this,"location_posttype"));
    add_shortcode("habit_challenge", array($this, "shortcode_func"));
  }

  function register()
  {
    add_action("admin_enqueue_scripts", array($this,"wp_google_scripts"));
    //add_action("add_meta_boxes", array($this,"add_location_meta_box"));
    add_action("admin_print_styles-post.php", array($this,"location_js_css"));
    add_action("admin_print_styles-post-new.php", array($this,"location_js_css"));
    add_action("save_post", array($this,"save_location"));
  }

  function activate()
  {
    //echo "activated";
    // generate CPT
    $this->location_posttype();
    flush_rewrite_rules();
  }

  function deactivate()
  {
    //echo "deactivated";
    // flush rewrite rules
    flush_rewrite_rules();
  }

  function uninstall()
  {
    // delete CPT
    // delete all the plugin data from DB?
    // look at file uninstall.php
  }

  function location_posttype()
  {
    $args = array(
      "label"=>"Locations",
      "labels"=>array(
        "add_new"=>"Add New Location"
      ),
      "public"=>TRUE,
      "supports"=>array(
        "title"//,
        //"excerpt"
      ),
      "register_meta_box_cb"=>array($this, "add_location_meta_box")
    );

    register_post_type( "location", $args);

  }

  function wp_google_scripts()
  {
    //$API_KEY = "AIzaSyAJVTiPtASirG67ydfd88Qd7lSq7XL6ELQ"; // API Key 1
    $API_KEY = "AIzaSyBJdLfvSniGEz9tH8_L7ujphtqKG7uQdNk"; // API Key 2
    wp_enqueue_script("google-maps-native", "http://maps.googleapis.com/maps/api/js?key=".$API_KEY."&callback=initMap");
  }

  function add_location_meta_box()
  {
    add_meta_box(
        "location_meta_box", // $id
        "Select Pin Location On Map", // $title
        array($this,"show_location_meta_box"), // $callback
        "location", // $screen
        "normal", // $context
        "high"//, // $priority
        // $callback_args
    );
  }

  function show_location_meta_box()
  {
    global $post;  
      $lat = get_post_meta($post->ID, "lat", TRUE);  
      $lng = get_post_meta($post->ID, "lng", TRUE); 
      $zoom = get_post_meta($post->ID, "zoom", TRUE); 
      $center = get_post_meta($post->ID, "center", TRUE); 
      $nonce = wp_create_nonce(basename(__FILE__));

    ?>
    <div class="location_map_canvas" id="location_map_canvas"></div>

    <input type="hidden" name="glat" id="latitude" value="<?php echo $lat; ?>">
    <input type="hidden" name="glng" id="longitude" value="<?php echo $lng; ?>">
    <input type="hidden" name="gzoom" id="zoom" value="<?php echo $zoom; ?>">
    <input type="hidden" name="gcenter" id="center" value="<?php echo $center; ?>">
    <input type="hidden" name="location_meta_box_nonce" value="<?php echo $nonce; ?>">  
    <?php
  }

  function location_js_css()
  {
    global $post;
    wp_enqueue_style("gmaps_meta_box", plugins_url("style.css", __FILE__ , time()));
    wp_enqueue_script("gmaps_meta_box", plugins_url("map.js", __FILE__ , time()));
    $helper = array(
      "lat" => get_post_meta($post->ID, "lat", TRUE),
      "lng" => get_post_meta($post->ID, "lng", TRUE),
      "zoom" => get_post_meta($post->ID, "zoom", TRUE),
      "center" => get_post_meta($post->ID, "center", TRUE),
      "view_only" => false
    );
    wp_localize_script("gmaps_meta_box", "helper", $helper);
  }

  function save_location($post_id)
  { 
    // verify nonce
    if (isset($_POST["location_meta_box_nonce"]))
    {
      if (!wp_verify_nonce($_POST["location_meta_box_nonce"], basename(__FILE__)))
        return $post_id;
    }
    else
    {
      return $post_id;
    }

    // update data lat, lng, zoom, center
    if (isset( $_POST["glat"]))
      update_post_meta($post_id, "lat", $_POST["glat"]);
    if (isset( $_POST["glng"]))
      update_post_meta($post_id, "lng", $_POST["glng"]);
    if (isset( $_POST["gzoom"]))
      update_post_meta($post_id, "zoom", $_POST["gzoom"]);
    if (isset( $_POST["gcenter"]))
      update_post_meta($post_id, "center", $_POST["gcenter"]);
  }

  public function shortcode_func($atts)
  {
    // insert into post/page using the following notation...
    // [habit_challenge title="{title}"]
    // eg. [habit_challenge title="North 1"]
  
    // assign or init args attributes
    $args = shortcode_atts( array(
      "title" => "",
    ), $atts );

    // init local vars
    $post_ID = 0;
    $post_title = $args["title"];
    $post_meta_lat = 0;
    $post_meta_lng = 0;
    $post_meta_zoom = 0;
    $post_meta_center = "";

    // query db
    global $wpdb;

    // query db for post_ID
    $sql = "SELECT * FROM wp_posts WHERE post_title='".$post_title."';";
    $row = $wpdb->get_row($sql, OBJECT); // OBJECT is default anyways
    if (!empty($row)) {
      //echo "post_title: " . $row->post_title . "<br>";
      //echo "post_id: " . $row->ID . "<br>";
      $post_ID = $row->ID;
    } else {
      return "ERROR: Specified Location Title Not Found";
    }

    // query db for post_meta data
    $sql = "SELECT * FROM wp_postmeta WHERE post_id=$post_ID;";
    $result = $wpdb->get_results($sql, OBJECT);
    if (!empty($result)) {
      foreach ($result as $row) {
        if ($row->meta_key == "lat")
          $post_meta_lat = $row->meta_value;
        if ($row->meta_key == "lng")
          $post_meta_lng = $row->meta_value;
        if ($row->meta_key == "zoom")
          $post_meta_zoom = $row->meta_value;
        if ($row->meta_key == "center")
          $post_meta_center = $row->meta_value;
      }
      //echo "$post_meta_lat $post_meta_lng $post_meta_zoom $post_meta_center<br/><br/>";
    } else {
      return "ERROR: Meta Data for Post Not Found";
    }

    // load google map with marker, zoom, and center
    $this->wp_google_scripts();
    wp_enqueue_style("gmaps_meta_box", plugins_url("style.css", __FILE__ , time()));
    wp_enqueue_script("gmaps_meta_box", plugins_url("map.js", __FILE__ , time()));
    $helper = array(
      "lat" => $post_meta_lat,
      "lng" => $post_meta_lng,
      "zoom" => $post_meta_zoom,
      "center" => $post_meta_center,
      "view_only" => true
    );
    wp_localize_script("gmaps_meta_box", "helper", $helper);

    // output of location title & map in post
    ?>
    <span id="location_title">
    <?=$post_title;?> <!--shorthand php notation required on web server-->
    </span><br/>
    <div class="location_map_canvas" id="location_map_canvas"></div>
    <?php

    return "";

  }

}

if (class_exists("HabitChallengePlugin"))
{
  $habitChallengePlugin = new HabitChallengePlugin();
  $habitChallengePlugin->register();
}

// activation
register_activation_hook(__FILE__, array($habitChallengePlugin, "activate"));

// deactivation
register_deactivation_hook(__FILE__, array($habitChallengePlugin, "deactivate"));

// uninstall
//register_uninstall_hook(__FILE__, array($habitChallengePlugin, "uninstall"));

