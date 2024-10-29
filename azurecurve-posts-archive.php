<?php
/*
Plugin Name: azurecurve Posts Archive
Plugin URI: http://development.azurecurve.co.uk/plugins/posts-archive

Description: Posts Archive (multi-site compatible) based on Ozh Tweet Archive Theme; archive can be displayed in a widget, post or page.
Version: 2.0.2

Author: azurecurve
Author URI: http://development.azurecurve.co.uk

Text Domain: azc_pa
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

//include menu
require_once( dirname(  __FILE__ ) . '/includes/menu.php');

// Register function to be called when widget initialization occurs
add_action( 'widgets_init', 'azc_pa_create_widget' );

// Create new widget
function azc_pa_create_widget() {
	register_widget( 'azc_pa_register_archive' );
}

// Widget implementation class
class azc_pa_register_archive extends WP_Widget {
	// Constructor function
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		
		// Widget creation function
		parent::__construct( 'azc_pa',
							 'azurecurve Posts Archive',
							 array( 'description' =>
									__('Displays Posts Archive', 'azc_pa') ) );
	}

	/**
	 * enqueue function.
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		// Enqueue Styles
		wp_enqueue_style( 'azurecurve-posts-archive', plugins_url( 'style.css', __FILE__ ), '', '1.0.0' );
	}

	// Code to render options form
	function form( $instance ) {
		// Retrieve previous values from instance
		// or set default values if not present
		$widget_title = ( !empty( $instance['azc_pa_title'] ) ? 
							esc_attr( $instance['azc_pa_title'] ) :
							__('Posts Archive', 'azc_pa') );
		?>

		<!-- Display field to specify title  -->
		<p>
			<label for="<?php echo 
						$this->get_field_id( 'azc_pa_title' ); ?>">
			<?php echo 'Widget Title:'; ?>			
			<input type="text" 
					id="<?php echo $this->get_field_id( 'azc_pa_title' ); ?>"
					name="<?php echo $this->get_field_name( 'azc_pa_title' ); ?>"
					value="<?php echo $widget_title; ?>" />			
			</label>
		</p> 

		<?php
	}

	// Function to perform user input validation
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['azc_pa_title'] =
			strip_tags( $new_instance['azc_pa_title'] );

		return $instance;
	}
	
	// Function to display widget contents
	function widget ( $args, $instance ) {
		// Extract members of args array as individual variables
		extract( $args );

		// Display widget title
		echo $before_widget;
		echo $before_title;
		$widget_title = ( !empty( $instance['azc_pa_title'] ) ? 
					esc_attr( $instance['azc_pa_title'] ) :
					__('Posts Archive', 'azc_pa') );
		echo apply_filters( 'widget_title', $widget_title );
		echo $after_title; 

		global $wpdb;
		
		$where = "WHERE post_type = 'post' AND post_status = 'publish'";
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY YEAR DESC, MONTH ASC";
		$_archive = $wpdb->get_results( $query );

		$last_year  = (int) $_archive[0]->year;
		$first_year = (int) $_archive[ count( $_archive ) - 1 ]->year;

		$archive    = array();
		$max        = 0;
		$year_total = array();
		
		foreach( $_archive as $data ) {
			if( !isset( $year_total[ $data->year ] ) ) {
				$year_total[ $data->year ] = 0;
			}
			$archive[ $data->year ][ $data->month ] = $data->posts;
			$year_total[ $data->year ] += $data->posts;
			$max = max( $max, $data->posts );
		}
		unset( $_archive );

		for ( $year = $last_year; $year >= $first_year; $year-- ) {
			echo '<div class="azc_pa_widget_archive_year">';
			echo '<span class="azc_pa_widget_archive_year_label">' . $year;
			if( isset( $year_total[$year] ) ) {
				echo '<span class="azc_pa_widget_archive_year_count">' . $year_total[$year] . ' '.__('posts', 'azc_pa').'</span>';
			}
			echo '</span>';
			echo '<ol class="azc_pa_widget_ordered_list">';
			for ( $month = 1; $month <= 12; $month++ ) {
				$num = isset( $archive[ $year ][ $month ] ) ? $archive[ $year ][ $month ] : 0;
				$empty = $num ? 'azc_pa_widget_not_empty' : 'azc_pa_widget_empty';
				echo "<li class='$empty'>";
				$height = 100 - max( floor( $num / $max * 100 ), 20 );
				if( $num ) {
					$url = get_month_link( $year, $month );
					$m = str_pad( $month, 2, "0", STR_PAD_LEFT);
					echo "<a href='$url' title='$m/$year : $num ".__('posts', 'azc_pa')."'><span class='azc_pa_widget_bar_wrap'><span class='azc_pa_widget_bar' style='height:$height%'></span></span>";
					echo "<span class='azc_pa_widget_label'>" . $m . "</span>";
					echo "</a>";
				}
				echo '</li>';
			}
			echo '</ol>';
			echo "</div>";
		}
		// Reset post data query
		wp_reset_query();

		echo $after_widget;
	}
}



function display_posts_archive($atts) {
	global $wpdb;
	
	$where = "WHERE post_type = 'post' AND post_status = 'publish'";
	$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY YEAR DESC, MONTH ASC";
	$_archive = $wpdb->get_results( $query );

	$last_year  = (int) $_archive[0]->year;
	$first_year = (int) $_archive[ count( $_archive ) - 1 ]->year;

	$archive    = array();
	$max        = 0;
	$year_total = array();
	
	foreach( $_archive as $data ) {
		if( !isset( $year_total[ $data->year ] ) ) {
			$year_total[ $data->year ] = 0;
		}
		$archive[ $data->year ][ $data->month ] = $data->posts;
		$year_total[ $data->year ] += $data->posts;
		$max = max( $max, $data->posts );
	}
	unset( $_archive );

	for ( $year = $last_year; $year >= $first_year; $year-- ) {
		echo '<div class="azc_pa_page_archive_year">';
		echo '<span class="azc_pa_page_archive_year_label">' . $year;
		if( isset( $year_total[$year] ) ) {
			echo '<span class="azc_pa_page_archive_year_count">' . $year_total[$year] . ' '.__('posts', 'azc_pa').'</span>';
		}
		echo '</span>';
		echo '<ol class="azc_pa_page_ordered_list">';
		for ( $month = 1; $month <= 12; $month++ ) {
			$num = isset( $archive[ $year ][ $month ] ) ? $archive[ $year ][ $month ] : 0;
			$empty = $num ? 'azc_pa_page_not_empty' : 'azc_pa_page_empty';
			echo "<li class='$empty'>";
			$height = 100 - max( floor( $num / $max * 100 ), 20 );
			if( $num ) {
				$url = get_month_link( $year, $month );
				$m = str_pad( $month, 2, "0", STR_PAD_LEFT);
				echo "<a href='$url' title='$m/$year : $num ".__('posts', 'azc_pa')."'><span class='azc_pa_page_bar_wrap'><span class='azc_pa_page_bar' style='height:$height%'></span></span>";
				echo "<span class='azc_pa_page_label'>" . $m . "</span>";
				echo "</a>";
			}
			echo '</li>';
		}
		echo '</ol>';
		echo "</div>";
	}
	// Reset post data query
	wp_reset_query();
}
add_shortcode( 'posts-archive', 'display_posts_archive' );

// Add actions
function azc_pa_load_plugin_textdomain(){
	
	$loaded = load_plugin_textdomain( 'azc_pa', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}
add_action('plugins_loaded', 'azc_pa_load_plugin_textdomain');


// azurecurve menu
function azc_create_pa_plugin_menu() {
	global $admin_page_hooks;
    
	add_submenu_page( "azc-plugin-menus"
						,"Post Archive"
						,"Post Archive"
						,'manage_options'
						,"azc-pa"
						,"azc_pa_settings" );
}
add_action("admin_menu", "azc_create_pa_plugin_menu");

function azc_pa_settings() {
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to access this page.' , 'azc_pa'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	?>
	<div id="azc-t-general" class="wrap">
			<h2>azurecurve Posts Archive</h2>
			<p>
				<?php _e('The Posts Archive plugin allows a posts archive to be displayed using the plugins widget or in posts and pages through the use of the <strong>posts-archive</strong> shortcode. ', 'azc_pa'); ?>
			</p>
	</div>
	
<?php
}

?>