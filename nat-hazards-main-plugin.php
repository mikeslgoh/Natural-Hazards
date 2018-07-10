<?php
/**
 * TODO: Added by Nathan previously; edited the details but may need to double check license
 *
 * Plugin Name: Natural Hazards
 * Plugin URI:
 * Description: This plugin contains shortcode for creating, the nat_hazards filter bar ([nat_hazards_filter]), search page([nat_hazards_search]), map page ([nat_hazards_map]), aggregated search results page ([nat_hazards_results] both as a list ([nat_hazards_list]) and as a map([nat_hazards_map])) and individual soil site page ([nat_hazards_site]).
 * It also has some supporting JavaScript and CSS files.
 * Version: 1.1.1
 * Author: Michael Goh
 * License: GPL2

 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 * @package WordPress
 */

// GLOBAL VARIABLE.
$dropdown_categories = [
	'item_type',
	'experience',
	'assoc_institutions',
	'assoc_courses',
	'tour_name',
	'city',
	'region',
	'country',
	'framework_concept',
	'source1_type',
	'source2_type',
	'indepth_type',
];

$text_field_categories = [
	'short_description',
	'long_description',
	'study_questions',
	'item_name',
];

/**
 * Queues the nat_hazards-style stylesheet, enabling formatting for nat_hazards panes
 *
 * @since     1.0.0
 */
function nat_hazards_enqueue_style_1() {
	wp_enqueue_style( 'nat_hazards-style-1', plugins_url( '/css/nat-hazards-style.css', __FILE__ ) );
}

	add_action( 'wp_enqueue_scripts', 'nat_hazards_enqueue_style_1' );

/**
 * Generates code for 'maketabs' shortcode, enqueuing nat_hazards-tabs, which enables tabs in a page
 *
 * @since     1.0.0
 */
function nat_hazards_tabs() {
	wp_enqueue_script( 'nat_hazards-script-1', plugins_url( '/js/nat-hazards-tabs.js', __FILE__ ), array( 'jquery-ui-tabs' ) );
}

	add_shortcode( 'nat-hazards-tabs', 'nat_hazards_tabs' );

/**
 * Generates code for 'nat-hazards-accordions' shortcode, enqueuing nat_hazards-accordions, which enables accordions in a page
 *
 * @since     1.0.0
 */
function nathazards_accordions() {
	wp_enqueue_script( 'nat_hazards-script-2', plugins_url( '/js/nat-hazards-accordions.js', __FILE__ ), array( 'jquery-ui-accordion' ) );
}

	add_shortcode( 'nat-hazards-accordions', 'nathazards_accordions' );
/**
 * Generates code for 'makesearchtech' shortcode, enqueuing nat_hazards-list, which enables the conditional drop-down list for soil orders, great groups, and subgroups
 *
 * @since     1.0.0
 */
function nat_hazards_searchtech() {
	wp_enqueue_script( 'nat_hazards-script-3', plugins_url( '/js/nat-hazards-list.js?', __FILE__ ) );
}

	add_shortcode( 'makesearchtech', 'nat_hazards_searchtech' );

/**
 * Queries the Fusion Table, returning a std object
 *
 * @since     1.0.0
 * @return    array  PHP array of results
 */
function nat_hazards_ft_query() {
	$json_start = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+*+FROM+';

	$json_start .= get_option( 'nat_hazards_ft_address' );

	$json_query = nat_hazards_FT_queryParams();

	$json_sort  = '+ORDER+BY+tour_stop+';
	$json_where = '+WHERE+';

	$json_end = '&key=';

	$json_end .= get_option( 'nat_hazards_ft_key' );

	if ( '' !== $json_query ) {
		$json_start .= $json_where;
	}

	$json_start .= $json_query;

	$json_start .= $json_sort;

	$json_start .= $json_end;

	$json_data = wpcom_vip_file_get_contents( $json_start );

	$php_data = json_decode( $json_data );

	$nat_hazards_ft_results = nat_hazards_object_to_Array( $php_data );

	return $nat_hazards_ft_results;
}

/**
 * General query to the Fusion Table
 *
 * @since 1.0.0
 * @return    array  PHP array of results
 */
function nat_hazards_ft_general_query() {
	$json_start = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+*+FROM+';

	$json_start .= get_option( 'nat_hazards_ft_address' );

	$json_start .= '+ORDER+BY+tour_stop+';

	$json_start .= '&key=';

	$json_start .= get_option( 'nat_hazards_ft_key' );

	$json_data = wpcom_vip_file_get_contents( $json_start );

	$php_data = json_decode( $json_data );

	return nat_hazards_object_to_array( $php_data );
}

/**
 * Helper function to add query paramaters
 *
 * @since 1.0.0
 * @return string  query params.
 */
function nat_hazards_ft_query_params() {
	$json_query = '';

	if ( isset( $_REQUEST['id'] ) && '' !== $_REQUEST['id'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) ) ) {
		$json_query .= "tour_stop='" . rawurlencode( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) ) . "'";
	}

	$index = 0;

	foreach ( $GLOBALS['dropdown_categories'] as $field ) {
		if ( isset( $_REQUEST[ $field ] ) && '' !== $_REQUEST[ $field ] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) ) ) {
			if ( 0 === $index ) {
				$json_query .= $field . "='" . sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) . "'";
				$index++;
			} else {
				$json_query .= '+AND+' . $field . "='" . sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) . "'";
			}
		}
	}
	return $json_query;
}

/**
 * Generates code for 'nat-hazards-list' shortcode, creating a list of search results
 *
 * @since     1.0.0
 * @return    string  HTML
 * @param array $nat_hazards_ft_results fusion table results.
 */
function nat_hazards_list( $nat_hazards_ft_results ) {
	$sites = get_query_data( $nat_hazards_ft_results );
	$temp = count( $sites );

	// Make a table with a header for the information.
	$table_html = '
            <table align="center" cellspacing="3" cellpadding="3" width="100%" style="margin:0px">
                <tr>
                    <td align="left"><strong>Tour Stop</strong></td>
                    <td align="left"><strong>Site Name</strong></td>
                    <td align="left"><strong>Type</strong></td>
                    <td align="left"><strong>Framework concept</strong></td>
                </tr>
        ';

	// dummysite remove !!!
	for ( $i = 0; $i < $temp; $i++ ) {
		$table_html .= '
                <tr>
                    <td align="left">' . esc_html( $sites [ $i ]['tour_stop'] ) . '</td>
                    <td align="left"><a target="blank_" href="../sea2Sky-site-page/?id=' . esc_html( $sites [ $i ]['tour_stop'] ) . '">' . esc_html( $sites[ $i ]['item_name'] ) . '</a></td>
                    <td align="left">' . esc_html( $sites[ $i ]['item_type'] ) . '</td>
                    <td align="left">' . esc_html( $sites[ $i ]['framework_concept'] ) . '</td>
                </tr>
            ';
	}

	$table_html .= '
            </table>
        ';

	return $table_html;
}

	add_shortcode( 'nat-hazards-list', 'nat_hazards_list' );

/**
 * Generates code for 'nat-hazards-map' shortcode, creating a map in a page
 *
 * @since     1.0.0
 * @return    string  HTML
 */
function nat_hazards_map() {
		wp_enqueue_script( 'nat_hazards-script-4', 'https://www.google.com/jsapi', array(), 1, true );
		wp_enqueue_script( 'nat_hazards-script-5', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyAogLQgkZED4Mv6uDZfb4XWpoFG63zUaZ0', array(), 1, true );

		$maps_query = '/js/nat-hazards-maps.php?nat_hazards_ft_address=' . get_option( 'nat_hazards_ft_address' ) . '';
		$maps_query .= get_map_query()();

		wp_enqueue_script( 'nat_hazards-script-6', plugins_url( $maps_query, __FILE__ ), array( 'nat_hazards-script-4', 'nat_hazards-script-5' ), 1, true );

		return '<div id="googft-mapCanvas" style="width:100%; height:550px;"></div>';
}

	add_shortcode( 'nat-hazards-map', 'nat_hazards_map' );

/**
 * Helper function to pull the paramaters to be quried for map pins
 *
 * @since 1.0.0
 * @return string  map params for query.
 */
function get_map_query() {
	$json_query = '';

	foreach ( $GLOBALS['dropdown_categories'] as $field ) {
		if ( isset( $_REQUEST[ $field ] ) && '' !== $_REQUEST[ $field ] ) {
			$json_query .= '&' . $field . '=' . urlencode( sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) ) . '';
		}
	}

	return $json_query;
}

/**
 * Generates code for search results, calling nat_hazards_map and nat_hazards_list
 *
 * @since     1.0.0
 * @return string  HTML.
 */
function nat_hazards_results() {
	$nat_hazards_ft_results = nat_hazards_FT_query();

	if ( 2 !== count( $nat_hazards_ft_results ) ) {
		$map  = nat_hazards_map( $nat_hazards_ft_results );
		$list = nat_hazards_list( $nat_hazards_ft_results );

		$complete_results = '<div id="nat_hazards-tabs">
                <div id="menu-wrap">
                    <ul>
                        <li><a href="#map">Mapped Results</a></li>
                        <li><a href="#list">Listed Results</a></li>
                    </ul>
                </div>
                <div class="nat_hazards-tabs-pane" id="map">';

		$complete_results .= $map;

		$complete_results .= '</div>
                <div class="nat_hazards-tabs-pane" id="list">';

		$complete_results .= $list;

		$complete_results .= '</div>
            </div>
        ';

		return $complete_results;
	} else {
		return '<p>No results found</p>';
	}
}
	add_shortcode( 'nat-hazards-results', 'nat_hazards_results' );

/**
 * Generates code for 'makesearch' shortcode, creating a search page
 *
 * @since     1.0.0
 * @return    string  HTML
 */
function nat_hazards_search() {
	$nat_hazards_ft_results = nat_hazards_FT_general_query();
	$sites = get_query_data( $nat_hazards_ft_results );
	$number_of_sites = count( $sites );

	$return_string =
	'
    <form name="nat_hazards_search" style="text-align: left" action="../sea2sky-results/" method="GET" onload="fillCategory();">
        <div class="nat_hazards-content-top">
            <div>
                <h3 style="margin: 0px; margin-top: 10px; padding: 0px; text-align: center">Basic Site Search</h3>
                <div style="text-align: center"> of ' . ( count( $sites ) - 1 ) . ' nat_hazards sites </div>
                    <table>';

	foreach ( $nat_hazards_ft_results['columns'] as $category ) {
		if ( in_array( $category, $GLOBALS['dropdown_categories'] ) ) {
			$return_string .= get_options_by_category( $category, $number_of_sites, $sites );
		}
	}

	// !!! edit the search params for key text searches.
	$return_string .= '
                    </table>
                    <input type="submit" value="Search">
                    <input type="reset" style="margin-bottom: 10px;" value="Reset" />
                </div>
            </div>

            <div class="nat_hazards-content-top">
            <div>

            <div id="nat_hazards-accordion-1">
                <div class="nat_hazards-accordion-title-1">
                    Location Search Criteria
                </div>
                <div class="nat_hazards-accordion-pane-1">
                    <table>
                        <tr>
                            <td>
                                Place Name (keyword):
                            </td>
                            <td>
                                <input type="text" name="place_name">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                City (keyword):
                            </td>
                            <td>
                                <input type="text" name="city">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Region (keyword):
                            </td>
                            <td>
                                <input type="text" name="region">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Country (keyword):
                            </td>
                            <td>
                                <input type="text" name="country">
                            </td>
                        </tr>
                            <tr>
                            <td>
                                Latitude/Longitude within <input type="number" name="degrees" min="0" style="width:40px"> &#176 of
                            </td>
                            <td>
                                <input type="number" name="latitude" style="margin-bottom: 2px"> &#176 latitude, <input type="number" name="longitude"> &#176 longitude
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Elevation between
                            </td>
                            <td>
                                <input type="number" name="min_ele"> and <input type="number" name="max_ele"> m
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
    ';

	$return_string .= '
                    </table>
                </div>
            </div>
        </div>
    </form>';

	return $return_string;
}

	add_shortcode( 'nat-hazards-search', 'nat_hazards_search' );

/**
 * Get options based on a given category for the sites
 *
 * @since 1.0.0
 * @param string $category - the category to find the options with.
 * @param number $total - the total number of sites.
 * @param array  $sites - the data from the sites.
 * @return string  HTML.
 */
function get_options_from_category( $category, $total, $sites ) {
	$table_html .= '
            <tr>
                <td style="text-align: right">' . strtoupper( str_replace( '_', ' ', $category ) ) . ' </td>
            <td>
	            <select style="min-width: 300px; max-width: 300px;" name=' . $category . '>
                <option value="">...</option>';

	$options = [];

	for ( $i = 0; $i < $total; $i++ ) {
		$option = $sites[ $i ][ $category ];

		if ( ! in_array( $option, $options ) && '' !== $option ) {
			array_push( $options, $option );
		}
	}

	foreach ( $options as $option ) {
		$table_html .= '
            <option value=' . $option . '>' . $option . '</option>
        ';
	}

	$return_string .= $table_html . '
    </select>
    </td>
    </tr>';

	return $return_string;
}

/**
 * Generates code for 'nat-hazards-site' shortcode, creating a soil site page
 *
 * @since     1.0.0
 * @return    string  HTML
 */
function nat_hazards_site() {

	if ( isset( $_REQUEST['id'] ) && '' !== $_REQUEST['id'] ) {
		$id = sanitize_text_field( wp_unslash( $_REQUEST['id'] ) );
	} else {
		$id = 0;
	}

	if ( $id ) {
		$nat_hazards_ft_results = nat_hazards_FT_query();
	}

	if ( 0 !== $id ) {
		if ( 2 !== count( $nat_hazards_ft_results ) ) {

			$selected_site = get_query_data( $nat_hazards_ft_results )[0];

			// Title content.
			$complete_site = get_title( $selected_site );

			$complete_site .= get_basic_facts( $selected_site );

			// Media content.
			$complete_site .= get_media( $selected_site );

			$complete_site .= get_dropdown_description( $selected_site ) . '<br>';

		} else {
			$complete_site = '
                <p>No valid site selected; please try again.</p>
            ';
		}
	} else {
		$complete_site = '
			<p>No valid site selected; please try again.</p>
		';
	}
	return $complete_site;
}

	add_shortcode( 'nat-hazards-site', 'nat_hazards_site' );

/**
 * Generate associative array for data from query
 *
 * @since 1.0.0
 * @param array $nat_hazards_ft_results  query results from Fusion Table.
 * @return array  the associative array for the query data.
 */
function get_query_data( $nat_hazards_ft_results ) {
	$data = array();
	$sites = $nat_hazards_ft_results['rows'];
	$column_names = $nat_hazards_ft_results['columns'];
	$num_rows = count( $sites );

	// Create an associative array so that columnName points to the row data for the individual site.
	for ( $row = 0; $row < $num_rows; $row++ ) {
		$row_data = $sites[ $row ];
		$index = 0;

		foreach ( $column_names as $column ) {
			$data[ $row ][ $column ] = $row_data[ $index ];
			$index++;
		}
	}
	return $data;
}

/* HELPER FUNCTIONS FOR INDIVIDUAL SITE. */

/**
 * Create title for an individual site page
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_title( $selected_site ) {
	return '<div class="nat_hazards-content-top">
            <h3>'
	. esc_html( $selected_site['item_name'] ) .
	', <span style="text-transform: uppercase">'
	. esc_html( $selected_site['item_type'] ) .
	'</span>
            </h3>
            </div>';
}

/**
 * Create basic facts for an individual site page
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_basic_facts( $selected_site ) {
	$facts = '
	<div class="nat_hazards-content-box">
        <h3><u>Basic Facts</u></h3>
        <p><strong>Focus is on: </strong> '
	. esc_html( $selected_site['framework_concept'] ) .
	'<br> <strong>Tour: </strong> '
	. esc_html( $selected_site['tour_name'] ) .
	', <strong>Stop: </strong> '
	. esc_html( $selected_site['tour_stop'] ) .
	'<br> <strong>Location: </strong> '
	. esc_html( $selected_site['country'] ) .
	', '
	. esc_html( $selected_site['region'] ) .
	', '
	. esc_html( $selected_site['city'] ) .
	'<br> <strong>Latitude & Longitude: </strong>'
	. esc_html( $selected_site['latitude'] ) .
	', ' . esc_html( $selected_site['longitude'] ) .
	'<br> <strong>Elevation: </strong>'
	. esc_html( $selected_site['elevn'] ) .
	'<br> <strong>Brief Description: </strong> '
	. esc_html( $selected_site['short_description'] ) .
	'<br> <strong>Example: </strong>
	    <a target="blank" href = '
	. esc_html( $selected_site['Google_URL'] ) .
		'> View on Google Maps 
		</a>
    </p>
    </div>';

	return $facts;
}

/**
 * Create dropdown descriptions for an individual site
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_dropdown_description( $selected_site ) {
	return '<div id="nat_hazards-accordion-1">
                    <div class="nat_hazards-accordion-title-1">
                        Description
                    </div>
                    <div class="nat_hazards-accordion-pane-1">'
	. esc_html( $selected_site['long_description'] ) .
	'</p>
                    </div>
                </div>

                <div id="nat_hazards-accordion-2">
                    <div class="nat_hazards-accordion-title-2">
                        Affiliations and learning tasks
                    </div>
                    <div class="nat_hazards-accordion-pane-1">
                        <p><strong>Submitted by: </strong>'
	. esc_html( $selected_site['name_submit'] ) .
	', on ' . esc_html( $selected_site['date_added'] ) .
	'
                        <br>
                        <span style="margin-left: 3%">
                        <strong>Institutions: </strong>'
	. esc_html( $selected_site['assoc_institutions'] ) .
	'<br>
                        </span>

                        <span style="margin-left: 3%">
                        <strong> Courses: </strong>'
	. esc_html( $selected_site['assoc_courses'] ) .
	'</span>

                        <br><br>
                        <strong>Study Questions: </strong> <br>'
	. esc_html( $selected_site['study_questions'] ) .
	'</p>
                    </div>
                </div>

                <div id="nat_hazards-accordion-3">
                    <div class="nat_hazards-accordion-title-3">
                        Sources/references
                    </div>
                    <div class="nat_hazards-accordion-pane-1">
                        <p>
                        <strong> <u>Source 1</u>: </strong>
                        <a style="color: blue" target="_blank" href=" ' . esc_html( $selected_site['source1_url'] ) . '" > Click Here </a>
                        <br> Type: '
	. esc_html( $selected_site['source1_type'] ) .
	'<br> <i>'
	. esc_html( $selected_site['source1_capt'] ) . '</i>
                        </p>
                        <p>
                        <strong> <u>Source 2</u>: </strong>
                        <a style="color: blue" target="_blank" href=" ' . esc_html( $selected_site['source2_url'] ) . '" > Click Here </a>
                        <br> Type: '
	. esc_html( $selected_site['source2_type'] ) .
	'<br> <i>'
	. esc_html( $selected_site['source2_capt'] ) . '</i>
                        </p>
                        <strong> <u>In-depth Source</u>: </strong>
                        <a style="color: blue" target="_blank" href=" ' . esc_html( $selected_site['indepth_url'] ) . '" > Click Here </a>
                        <br> Type: '
	. esc_html( $selected_site['indepth_type'] ) .
	'<br> <i>'
	. esc_html( $selected_site['indepth_capt'] ) . '</i>
                        </p>
                        </p>
                        <strong> <u>Interactive Media</u>: </strong>
                        <a style="color: blue" href="../sea2sky-iframe/?id=' . esc_html( $selected_site['tour_stop'] ) . '" target="_blank">
                        Click Here </a>
                        <br> Caption: '
	. esc_html( $selected_site['Holo_capt'] )
	. '</div>
                </div>
            ';
}

/**
 * Create details for an individual site
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_details( $selected_site ) {
	return '<div class="nat_hazards-content-box">
        <h3><u>Details</u></h3>
		' . esc_html( $selected_site['long_description'] ) .
		'</div>';
}

/**
 * Generate sources for an individual site
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_sources( $selected_site ) {
	return '<div class="nat_hazards-content-box">
	<h3>Sources</h3>
	<p>' .
	esc_html( $selected_site['other_sources'] ) .
	'
	</p>
	</div>';
}

/**
 * Generate media for an individual site
 *
 * @since 1.0.0
 * @param array $selected_site  query data for an individual site page.
 * @return string  HTML
 */
function get_media( $selected_site ) {
	return '
    <div class="nat_hazards-content-top">
        <img style="padding-top: 5%;" width=85% height=65% src='
	. esc_html( $selected_site['image1_url'] ) .
	'> <br>
        <i> <div style="text-align: center; font-size: 15px;">' . esc_html( $selected_site['image1_capt'] ) .
		'</div> </i>
        <br>
    ' .
		'</div>
    </div>';
}

/**
 * Generate iFrame link for an individual site
 *
 * @since 1.0.0
 * @return string  HTML
 */
function nat_hazards_create_iframe_site_link() {
	$iframe_site = '';

	if ( isset( $_REQUEST['id'] ) ) {
		$id = sanitize_text_field( wp_unslash( $_REQUEST['id'] ) );
	} else {
		$id = 0;
	}

	if ( $id ) {
		$json_query = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+Holo_iframe+FROM+';

		$json_query .= get_option( 'nat_hazards_ft_address' );
		$json_query .= '+WHERE+tour_stop=' . $id;
		$json_query .= '&key=';
		$json_query .= get_option( 'nat_hazards_ft_key' );
		$json_data = file_get_contents( $json_query );
		$php_data = json_decode( $json_data );

		$nat_hazards_ft_results = nat_hazards_object_to_array( $php_data );
	}

	if ( 0 != $id ) {
		if ( 2 != count( $nat_hazards_ft_results ) ) {
			$iframe_site = get_query_data( $nat_hazards_ft_results )[0];
			$complete_site = $iframe_site['Holo_iframe'];
		} else {
			$complete_site = '
                <p>No valid site provided; please try again.</p>
            ';
		}
	} else {
		$complete_site = '
        <p>No valid site provided; please try again.</p>
    ';
	}

	return $complete_site;
}

	add_shortcode( 'nat-hazards-iframe', 'nat_hazards_create_iframe_site_link' );

/**
 * Consumes a std object and makes from it a PHP array
 *
 * @since     1.0.0
 * @param     object $nat_hazards_ft_return  an std object returned from a query.
 * @return    array PHP array
 */
function nat_hazards_object_to_array( $nat_hazards_ft_return ) {
	if ( is_object( $nat_hazards_ft_return ) ) {
		// Gets the properties of the given object with get_object_vars function.
		$nat_hazards_ft_return = (array) $nat_hazards_ft_return;
	}

	if ( is_array( $nat_hazards_ft_return ) ) {
		// Return array converted to object Using __FUNCTION__ (Magic constant for recursive call.
		return array_map( __FUNCTION__, $nat_hazards_ft_return );
	} else {
		// Return array.
		return $nat_hazards_ft_return;
	}
}

/**
 * Generates code for the Options/Admin page
 *
 * @since     1.0.0
 */
function nat_hazards_options_initializer() {
	// TODO: check all this works.
	add_settings_section( 'nat_hazards_ft_data', '', 'nat_hazards_ft_data_callback_functions', 'nat_hazards_instruction_page' );

	add_settings_field( 'nat_hazards_ft_address', 'Fusion Table Address:', 'nat_hazards_ft_address', 'nat_hazards_instruction_page', 'nat_hazards_ft_data' );
	register_setting( 'nat_hazards_ft_data', 'nat_hazards_ft_address' );
	add_settings_field( 'nat_hazards_ft_key', 'Fusion Table Key:', 'nat_hazards_ft_key', 'nat_hazards_instruction_page', 'nat_hazards_ft_data' );
	register_setting( 'nat_hazards_ft_data', 'nat_hazards_ft_key' );

	add_option( 'nat_hazards_universities', array() );
	add_option( 'nat_hazards_courses', array() );
}

	add_action( 'admin_init', 'nat_hazards_options_initializer' );


/**
 * Generates code for Fusion Table address input
 *
 *  @since  1.0.0
 */
function nat_hazards_ft_address() {
	echo '<input type="text" id="ft_address_id" name="nat_hazards_ft_address"	value="';
	echo esc_attr( get_option( 'nat_hazards_ft_address' ) );
	echo '" /><br />';
}

/**
 * Generate code for Fusion Table key input
 *
 * @since  1.0.0
 */
function nat_hazards_ft_key() {
	echo '<input type="text" id="ft_key_id" name="nat_hazards_ft_key" value="';
	echo esc_attr( get_option( 'nat_hazards_ft_key' ) );
	echo '" />';
}

/**
 * Callback function for add settings section
 *
 * @since 1.0.0
 */
function nat_hazards_ft_data_callback_functions() { }


/**
 * Generate instructions for plugin
 *
 * @since 1.0.0
 */
function nat_hazards_instructions_initializer() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	if ( isset( $_POST['submit'] ) ) {
		if ( ! isset( $_POST['nat_hazards_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nat_hazards_nonce_field'] ) ), 'nat_hazards_nonce_check' ) ) {
			print 'Sorry, your nonce did not verify.';
			exit;
		}
	}

	nat_hazards_tabs();
	nat_hazards_enqueue_style_1();

	// TODO: update plugin settings for data that can be added/deleted.
	if ( isset( $_REQUEST['nat_hazards_universities_to_add'] ) && '' !== $_REQUEST['nat_hazards_universities_to_add'] ) {
		$temp_text  = sanitize_text_field( wp_unslash( $_REQUEST['nat_hazards_universities_to_add'] ) );
		$temp_array = get_option( 'nat_hazards_universities' );
		$temp_array[ $temp_text ] = $temp_text;
		update_option( 'nat_hazards_universities', $temp_array );
	}
	if ( isset( $_REQUEST['nat_hazards_universities_to_delete'] ) && '' !== $_REQUEST['nat_hazards_universities_to_delete'] ) {
		$temp_text  = sanitize_text_field( wp_unslash( $_REQUEST['nat_hazards_universities_to_delete'] ) );
		$temp_array = get_option( 'nat_hazards_universities' );
		unset( $temp_array[ $temp_text ] );
		update_option( 'nat_hazards_universities', $temp_array );
	}
	if ( isset( $_REQUEST['nat_hazards_courses_to_add'] ) && '' !== $_REQUEST['nat_hazards_courses_to_add'] ) {
		$temp_text = sanitize_text_field( wp_unslash( $_REQUEST['nat_hazards_courses_to_add'] ) );
		$temp_array = get_option( 'nat_hazards_courses' );
		$temp_array[ $temp_text ] = $temp_text;
		update_option( 'nat_hazards_courses', $temp_array );
	}
	if ( isset( $_REQUEST['nat_hazards_courses_to_delete'] ) && '' !== $_REQUEST['nat_hazards_courses_to_delete'] ) {
		$temp_text = sanitize_text_field( wp_unslash( $_REQUEST['nat_hazards_courses_to_delete'] ) );
		$temp_array = get_option( 'nat_hazards_courses' );
		unset( $temp_array[ $temp_text ] );
		update_option( 'nat_hazards_courses', $temp_array );
	}
	include 'nat-hazards-instruction-maker.php';
}

/**
 * Generate instructions page
 *
 * @since 1.0.0
 */
function nat_hazards_instructions_page() {
	add_menu_page( 'nat_hazards Instructions and Options', 'nat_hazards', 'manage_options', 'nat_hazards-instruction-page', 'nat_hazards_instructions_initializer' );
}

	add_action( 'admin_menu', 'nat_hazards_instructions_page' );
