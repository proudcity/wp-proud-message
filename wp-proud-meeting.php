<?php
/*
Plugin Name: Proud Meeting
Plugin URI: http://proudcity.com/
Description: Declares an Meeting custom post type.
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: Affero GPL v3
*/

namespace Proud\Meeting;

// Load Extendible
// -----------------------
if ( ! class_exists( 'ProudPlugin' ) ) {
  require_once( plugin_dir_path(__FILE__) . '../wp-proud-core/proud-plugin.class.php' );
}

class ProudMeeting extends \ProudPlugin {

  public function __construct() {
    parent::__construct( array(
      'textdomain'     => 'wp-proud-meeting',
      'plugin_path'    => __FILE__,
    ) );

    $this->post_type = 'meeting';
    $this->taxonomy = 'meeting-taxonomy';

    $this->hook( 'init', 'create_meeting' );
    $this->hook( 'rest_api_init', 'meeting_rest_support' );
    $this->hook( 'init', 'create_taxonomy' );

  }

  public function create_meeting() {
      $labels = array(
          'name'               => _x( 'Meetings', 'post name', 'wp-meeting' ),
          'singular_name'      => _x( 'Meeting', 'post type singular name', 'wp-meeting' ),
          'menu_name'          => _x( 'Meetings', 'admin menu', 'wp-meeting' ),
          'name_admin_bar'     => _x( 'Meeting', 'add new on admin bar', 'wp-meeting' ),
          'add_new'            => _x( 'Add New', 'meeting', 'wp-meeting' ),
          'add_new_item'       => __( 'Add New Meeting', 'wp-meeting' ),
          'new_item'           => __( 'New Meeting', 'wp-meeting' ),
          'edit_item'          => __( 'Edit Meeting', 'wp-meeting' ),
          'view_item'          => __( 'View Meeting', 'wp-meeting' ),
          'all_items'          => __( 'All Meetings', 'wp-meeting' ),
          'search_items'       => __( 'Search meeting', 'wp-meeting' ),
          'parent_item_colon'  => __( 'Parent meeting:', 'wp-meeting' ),
          'not_found'          => __( 'No meetings found.', 'wp-meeting' ),
          'not_found_in_trash' => __( 'No meetings found in Trash.', 'wp-meeting' )
      );

      $args = array(
          'labels'             => $labels,
          'description'        => __( 'Description.', 'wp-meeting' ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,
          'rewrite'            => array( 'slug' => 'meetings' ),
          'capability_type'    => 'post',
          'has_archive'        => false,
          'hierarchical'       => false,
          'menu_position'      => null,
          'show_in_rest'       => true,
          'rest_base'          => 'meetings',
          'rest_controller_class' => 'WP_REST_Posts_Controller',
          'supports'           => array( 'title', 'thumbnail',)
      );

      register_post_type( $this->post_type, $args );
  }

  function create_taxonomy() {
    register_taxonomy(
        $this->taxonomy,
        $this->post_type,
        array(
            'labels' => array(
                'name' => 'Meeting Categories',
                'add_new_item' => 'Add New Meeting Category',
                'new_item_name' => "New Meeting"
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        )
    );
  }

  public function meeting_rest_support() {
    register_rest_field( 'meeting',
          'meta',
          array(
              'get_callback'    => array( $this, 'meeting_rest_metadata' ),
              'update_callback' => null,
              'schema'          => null,
          )
    );
  }

  /**
   * Alter the REST endpoint.
   * Add metadata to the post response
   */
  public function meeting_rest_metadata( $object, $field_name, $request ) {


  }
} // class
new ProudMeeting;

// MeetingAddress meta box
class MeetingDetails extends \ProudMetaBox {

  public $options = [  // Meta options, key => default
    'datetime' => '',
    'location' => '',
    'agency' => '',
  ];

  public function __construct() {
    parent::__construct(
      'meeting_datetime', // key
      'Details', // title
      'meeting', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }

    // Get locations
    $locations = get_posts( [
      'post_type' => 'proud_location',
      'orderby' => 'post_title',
      'posts_per_page' => 1000
    ] );
    $location_options = ['' => '- Select one -'];
    if( !empty( $locations ) && empty( $locations['errors'] ) ) {
      foreach ( $locations as $location ) {
        $location_options[$location->ID] = $location->post_title;
      }
    }

    // Get Agencies
    $agencies = get_posts( [
      'post_type' => 'agency',
      'orderby' => 'post_title',
      'posts_per_page' => 1000
    ] );
    $agency_options = ['' => '- Select one -'];
    if( !empty( $agencies ) && empty( $agencies['errors'] ) ) {
      foreach ( $agencies as $agency ) {
        $agency_options[$agency->ID] = $agency->post_title;
      }
    }

    $this->fields = [
      'datetime' => [
        '#type' => 'text',
        '#title' => __pcHelp('Date and Time'),
      ],
      'location' => [
        '#type' => 'select',
        '#options' => $location_options,
        '#title' => __pcHelp('Location'),
        '#description' => __pcHelp('<a href="/wp-admin/edit.php?post_type=proud_location" target="_blank">Manage Locations</a>'),
      ],
      'agency' => [
        '#type' => 'select',
        '#options' => $agency_options,
        '#title' => _x( 'Agency', 'post type singular name', 'wp-agency' ),
        '#description' => __pcHelp('<a href="/wp-admin/edit.php?post_type=proud_location" target="_blank">Manage '. _x( 'Agencies', 'post name', 'wp-agency' ) .'</a>'),
      ],


      //@todo: location
    ];
  }

  /**
   * Saves form values
   */
  public function save_meta( $post_id, $post, $update ) {
    // Grab form values from Request
    $values = $this->validate_values( $post );
    if( !empty( $values ) ) {
      $this->save_all( $values, $post_id );
    }
  }
}
if( is_admin() )
  new MeetingDetails;


// MeetingAddress meta box
class MeetingAgenda extends \ProudMetaBox {

  public $options = [  // Meta options, key => default
    'agenda' => '',
    'agenda_attachment' => '',
  ];

  public function __construct() {
    parent::__construct(
      'meeting_agenda', // key
      'Agenda', // title
      'meeting', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }

    $this->fields = [
//      'agenda_wrapper' => [
//        '#type' => 'html',
//        '#html' => '<div id="agenda-wrapper"></div>'
//      ],
      'agenda' => [
        '#type' => 'editor',
        '#title' => __pcHelp('Agenda Text'),
      ],
      'agenda_attachment' => [
        '#type' => 'select_file',
        '#title' => __pcHelp('Attachment'),
      ],
    ];
  }


  /**
   * Saves form values
   */
  public function save_meta( $post_id, $post, $update ) {
    // Grab form values from Request
    $values = $this->validate_values( $post );
    if( !empty( $values ) ) {
      $this->save_all( $values, $post_id );
    }
  }
}
if( is_admin() )
  new MeetingAgenda;



// MeetingAddress meta box
class MeetingMinutes extends \ProudMetaBox {

  public $options = [  // Meta options, key => default
    'minutes' => '',
    'minutes_attachment' => '',
  ];

  public function __construct() {
    parent::__construct(
      'meeting_minutes', // key
      'Minutes', // title
      'meeting', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }

    $this->fields = [
      'minutes' => [
        '#type' => 'editor',
        '#title' => __pcHelp('Minutes Text'),
      ],
      'minutes_attachment' => [
        '#type' => 'select_file',
        '#title' => __pcHelp('Attachment'),
      ],
    ];
  }


  /**
   * Saves form values
   */
  public function save_meta( $post_id, $post, $update ) {
    // Grab form values from Request
    $values = $this->validate_values( $post );
    if( !empty( $values ) ) {
      $this->save_all( $values, $post_id );
    }
  }
}
if( is_admin() )
  new MeetingMinutes;



// MeetingAddress meta box
class MeetingVideo extends \ProudMetaBox {

  public $options = [  // Meta options, key => default
    'video' => '',
    'youtube_bookmarks' => '',
  ];

  public function __construct() {
    parent::__construct(
      'meeting_video', // key
      'Video', // title
      'meeting', // screen
      'normal',  // position
      'high' // priority
    );
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }
    $path = plugins_url('assets/', __FILE__);

    $this->fields = [
      'video' => [
        '#type' => 'text',
        '#title' => __pcHelp('YouTube Video'),
        '#description' =>  __pcHelp('Enter the URL or ID of the YouTube video'),
      ],
      'youtube_bookmarks' => [
        '#title' => __pcHelp('bookmarks'),
        '#type' => 'text',
      ],
      'youtube_bookmarks_html' => [
        '#type' => 'html',
        '#html' => file_get_contents(__DIR__ . '/assets/html/youtube-bookmarks.php'),
      ],
    ];
  }


  /**
   * Prints form
   */
  public function settings_content( $post ) {
    parent::settings_content( $post );
    // Enqueue JS
    $path = plugins_url('assets/', __FILE__);
    wp_enqueue_script( 'moment-js', $path . 'vendor/bootstrap-datetimepicker/moment.min.js' );
    wp_enqueue_style( 'glyphicons-css', '//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css' );
    wp_enqueue_script( 'bootstrap-datetimepicker-js', $path . 'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js' );
    wp_enqueue_style( 'bootstrap-datetimepicker-css', $path . 'vendor/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css' );
    wp_enqueue_script( 'youtube-api', '//www.youtube.com/iframe_api' );
    wp_enqueue_script( 'handlebars', $path . 'vendor/handlebars.min.js' );
    wp_enqueue_style( 'proud-meeting-css', $path . 'css/proud-meeting.css' );
    wp_enqueue_script( 'proud-meeting-js', $path . 'js/proud-meeting.js' );
    wp_enqueue_script( 'proud-meeting-youtube-bookmarks-js', $path . 'js/youtube-bookmarks.js' );
//    // Get field ids
//    $options = $this->get_field_ids();
//    // Set global lat / lng
//    $options['lat'] = get_option('lat', true);
//    $options['lng'] = get_option('lng', true);
//    wp_localize_script( 'google-places-field', 'meeting', $options );
//    wp_enqueue_script( 'google-places-field' );

  }


  /**
   * Saves form values
   */
  public function save_meta( $post_id, $post, $update ) {
    // Grab form values from Request
    $values = $this->validate_values( $post );
    if( !empty( $values ) ) {
      $this->save_all( $values, $post_id );
    }
  }
}
if( is_admin() )
  new MeetingVideo;

//
//
//// Meeting desc meta box (empty for body)
//class MeetingDescription extends \ProudMetaBox {
//
//  public $options = [  // Meta options, key => default
//  ];
//
//  public function __construct() {
//    parent::__construct(
//      'meeting_description', // key
//      'Description', // title
//      'meeting', // screen
//      'normal',  // position
//      'high' // priority
//    );
//  }
//
//  /**
//   * Called on form creation
//   * @param $displaying : false if just building form, true if about to display
//   * Use displaying:true to do any difficult loading that should only occur when
//   * the form actually will display
//   */
//  public function set_fields( $displaying ) {
//    $this->fields = [];
//  }
//}
//if( is_admin() )
//  new MeetingDescription;


// Meeting desc meta box (empty for body)
class MeetingCategory extends \ProudTermMetaBox {

  public $options = [  // Meta options, key => default                             
    'icon' => '',
    'color' => '',
  ];

  public function __construct() {
    parent::__construct( 
      'meeting-taxonomy', // key
      'Settings' // title
    );
  }

  private function colors() {
    return [
      '' => ' - Select - ',
      '#ED9356' => 'Orange',
      '#456D9C' => 'Blue',
      '#E76C6D' => 'Red',
      '#5A97C4' => 'Dark blue',
      '#4DC3FF' => 'Baby blue',
      '#9BBF6A' => 'Green',
    ];
  }

  /**
   * Called on form creation
   * @param $displaying : false if just building form, true if about to display
   * Use displaying:true to do any difficult loading that should only occur when
   * the form actually will display
   */
  public function set_fields( $displaying ) {
    // Already set, no loading necessary
    if( $displaying ) {
      return;
    }
    global $proudcore;

    $this->fields = [  
      'icon' => [
        '#title' => 'Icon',
        '#type' => 'fa-icon',
        '#default_value' => '',
        '#to_js_settings' => false
      ],
      'color' => [
        '#title' => 'Color',
        '#type' => 'select',
        '#options' => $this->colors(),
        '#default_value' => '',
        '#to_js_settings' => false
      ],
      'markup' => [
        '#type' => 'html',
        '#html' => '<style type="text/css">.term-description-wrap { display: none; }</style>',
      ],
    ];
  }

}
if( is_admin() )
  new MeetingCategory;

