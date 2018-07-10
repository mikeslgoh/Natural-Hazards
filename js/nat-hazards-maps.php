<?php
/**
 * PHP Google Maps Generator
 *
 * Description: This class builds the map used by retrieving the information from the Fusion Table based on the parameters set and creates pins on the map with the returned information.
 *
 * @package WordPress
 */

$temp = 0;

$json_query = '';
$index = 0;

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

if ( isset( $_REQUEST['id'] ) && '' !== $_REQUEST['id'] ) {
	$json_query .= "tour_stop='" . rawurlencode( sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) ) . "'";
	$index++;
}

foreach ( $dropdown_categories as $field ) {
	if ( isset( $_REQUEST[ $field ] ) && '' !== $_REQUEST[ $field ] ) {
		if ( 0 !== $index ) {
			$json_query .= $field . "='" . rawurlencode( sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) ) . "'";
			$index++;
		}
		else {
			$json_query .= ' AND '. $field . "='" . rawurlencode( sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) ) . "'";
		}
	}
}

// !!! does not include text search.
if ( isset( $_REQUEST['latitude'] ) && '' !== $_REQUEST['latitude'] ) {
	$temp = 0;
	if ( isset( $_REQUEST['degrees'] ) ) {
		$temp = sanitize_text_field( wp_unslash( $_REQUEST['degrees'] ) );
	}
	$min_lat = sanitize_text_field( wp_unslash( $_REQUEST['latitude'] ) ) - $temp;

	if ( $min_lat < -90 ) {
		$min_lat = -90;
	}

	$max_lat = sanitize_text_field( wp_unslash( $_REQUEST['latitude'] ) ) + $temp;

	if ( $max_lat > 90 ) {
		$max_lat = 90;
	}

	$json_query .= ' AND latitude>=' . $min_lat . '';
	$json_query .= ' AND latitude<=' . $max_lat . '';
}

if ( isset( $_REQUEST['longitude'] ) && '' !== $_REQUEST['longitude'] ) {
	$temp = 0;
	if ( isset( $_REQUEST['degrees'] ) ) {
		$temp = sanitize_text_field( wp_unslash( $_REQUEST['degrees'] ) );
	}

	$min_lon = sanitize_text_field( wp_unslash( $_REQUEST['longitude'] ) ) - $temp;

	if ( $min_lon < -180 ) {
		$min_lon = -180;
	}

	$max_lon = sanitize_text_field( wp_unslash( $_REQUEST['longitude'] ) ) + $temp;

	if ( $max_lon > 180 ) {
		$max_lon = 180;
	}

	$json_query .= ' AND longitude>=' . $min_lon . '';
	$json_query .= ' AND longitude<=' . $max_lon . '';
}

echo 'google.load(\'visualization\', \'1\', {\'packages\':[\'corechart\', \'table\', \'geomap\']});
		
var FT_TableID = ' . esc_js( sanitize_text_field( wp_unslash( $_REQUEST['nat_hazards_ft_address'] ) ) ) . '

var layer = null;

function initialize() {
	//SET CENTER
	map = new google.maps.Map(document.getElementById(\'googft-mapCanvas\'), {
			center: new google.maps.LatLng(49.942321,-123),
			zoom: 8,
			scrollwheel:false,
			mapTypeControl: true,
			mapTypeId: google.maps.MapTypeId.MAP,
			streetViewControl: false,
			overviewMapControl: true,
			mapTypeControlOptions: {
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
			},
			// CONTROLS
			zoomControl: true
		});

		// GET DATA
	layer = new google.maps.FusionTablesLayer({
		query: {
			select: \'latitude,longitude\',
			from: FT_TableID,
			where: "' . esc_js( $json_query ) . '"
		},
		options: {
			styleId: 2,
			templateId: 3,
		}
	});

	//SET MAP
	layer.setMap(map);

	var queryText = encodeURIComponent("SELECT \'latitude\', \'longitude\' FROM "+FT_TableID+" WHERE ' . esc_js( $json_query ) . '");
	var query = new google.visualization.Query(\'https://www.google.com/fusiontables/gvizdata?tq=\'  + queryText);
									
																											
	//set the callback function
	query.send(zoomTo);
}
											
function zoomTo(response) {

	numRows = response.getDataTable().getNumberOfRows();
	numCols = response.getDataTable().getNumberOfColumns();

	if(numRows != 0) {
		var bounds = new google.maps.LatLngBounds();
	for(i = 0; i < numRows; i++) {
		var point = new google.maps.LatLng(parseFloat(response.getDataTable().getValue(i, 0)),parseFloat(response.getDataTable().getValue(i, 1)));
		bounds.extend(point);
	}
	map.fitBounds(bounds);
}
}
	google.maps.event.addDomListener(window, "load", initialize);
';
