<?php

    /*
    TODO: Added by Nathan previously; edited the details but may need to double check license

     Plugin Name: SeaToSky
     Plugin URI:
     Description: This plugin contains shortcode for creating, the seaToSky filter bar ([seaToSky_filter]), search page([seaToSky_search]), map page ([seaToSky_map]), aggregated search results page ([seaToSky_results] both as a list ([seaToSky_list]) and as a map([seaToSky_map])) and individual soil site page ([seaToSky_site]). 
     It also has some supporting JavaScript and CSS files.
     Version: 1.1.1
     Author: Michael Goh
     License: GPL2

     This program is free software; you can redistribute it and/or modify
     it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program; if not, write to the Free Software
     Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

     */

    // GLOBAL VARIABLE
    $dropdownCategories = ['item_type', 'experience', 'assoc_institutions', 'assoc_courses',
    'tour_name', 'city', 'region', 'country', 'framework_concept', 'source1_type', 'source2_type', 'indepth_type'];
    $textFieldCategories =['short_description', 'long_description', 'study_questions', 'item_name'];
    

    /*
    Queues the seaToSky-style stylesheet, enabling formatting for seaToSky panes
    @since     1.0.0
    */
    function seaToSky_enqueue_style_1(){
        wp_enqueue_style('seaToSky-style-1', plugins_url( '/css/seaToSky-style.css' , __FILE__) );
    }

    add_action('wp_enqueue_scripts','seaToSky_enqueue_style_1');

    /*
    Generates code for 'maketabs' shortcode, enqueuing seaToSky-tabs, which enables tabs in a page
    @since     1.0.0
    */

    function seaToSky_tabs() {
        wp_enqueue_script('seaToSky-script-1', plugins_url( '/js/seaToSky-tabs.js' , __FILE__ ), array('jquery-ui-tabs'));
    }

    add_shortcode('stk-tabs', 'seaToSky_tabs');

    /*
    Generates code for 'stk-accordions' shortcode, enqueuing seaToSky-accordions, which enables accordions in a page
    @since     1.0.0
    */

    function seaToSky_accordions() {
        wp_enqueue_script('seaToSky-script-2', plugins_url( '/js/seaToSky-accordions.js' , __FILE__ ), array('jquery-ui-accordion'));
    }

    add_shortcode('stk-accordions', 'seaToSky_accordions');

    /*
    Generates code for 'makesearchtech' shortcode, enqueuing seaToSky-list, which enables the conditional drop-down list for soil orders, great groups, and subgroups
    @since     1.0.0
    */

    function seaToSky_searchtech() {
        wp_enqueue_script('seaToSky-script-3', plugins_url( '/js/seaToSky-list.js?' , __FILE__ ));
    }

    add_shortcode('makesearchtech', 'seaToSky_searchtech');

    /*
     Queries the Fusion Table, returning a std object
     @since     1.0.0
     @return    array  PHP array of results
     */

    function seaToSky_FT_query() {

        $jsonStart = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+*+FROM+';

        $jsonStart .= get_option('seaToSky_ft_address');

        $jsonQuery = seaToSky_FT_queryParams();

        $jsonSort = "+ORDER+BY+tour_stop+";
        $jsonWhere = "+WHERE+";

        $jsonEnd = '&key=';

        $jsonEnd .= get_option('seaToSky_ft_key');

        if($jsonQuery !== "")
            $jsonStart .= $jsonWhere;

        $jsonStart .= $jsonQuery;

        $jsonStart .= $jsonSort;

        $jsonStart .= $jsonEnd;

        $jsonData = file_get_contents($jsonStart);

        $PHPdata = json_decode($jsonData);

        $seaToSkyFTResults = seaToSky_objectToArray($PHPdata);

        return $seaToSkyFTResults;
    }

    function seaToSky_FT_general_query(){
        $jsonStart = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+*+FROM+';

        $jsonStart .= get_option('seaToSky_ft_address');

        $jsonStart .= "+ORDER+BY+tour_stop+";

        $jsonStart .= '&key=';

        $jsonStart .= get_option('seaToSky_ft_key');

        $jsonData = file_get_contents($jsonStart);

        $PHPdata = json_decode($jsonData);

        return seaToSky_objectToArray($PHPdata);
    }

    function seaToSky_FT_queryParams(){
        $jsonQuery = "";

        if(isset($_REQUEST['id']) && $_REQUEST['id'] != '')
            $jsonQuery .= "tour_stop='" . urlencode($_REQUEST['id']) . "'";
        
        $index = 0;

        foreach($GLOBALS['dropdownCategories'] as $field){
            if(isset($_REQUEST[$field]) && $_REQUEST[$field] != ''){
                if($index === 0)
                {
                    $jsonQuery .= $field ."='".$_REQUEST[$field]."'";
                    $index++;
                }
                else
                {
                    $jsonQuery .= '+AND+'.$field ."='". $_REQUEST[$field]."'";
                }
            }
        }
        
        // if(isset($_REQUEST['place_name']) && $_REQUEST['place_name'] != '')
        //     $jsonQuery .= "+AND+place_name+CONTAINS+IGNORING+CASE+'" . urlencode($_REQUEST['place_name']) . "'";
        
        return $jsonQuery;
    }
    /*
     Generates code for 'stk-list' shortcode, creating a list of search results
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_list($seaToSkyFTResults) {
        $sites = getQueryData($seaToSkyFTResults);
        $temp = sizeof($sites);

            // Make a table with a header for the information
            $tableHTML = '
                <table align="center" cellspacing="3" cellpadding="3" width="100%" style="margin:0px">
                    <tr>
                        <td align="left"><strong>Tour Stop</strong></td>
                        <td align="left"><strong>Site Name</strong></td>
                        <td align="left"><strong>Type</strong></td>
                        <td align="left"><strong>Framework concept</strong></td>
                    </tr>
            ';

            // dummysite remove !!!
            for($i = 0; $i < $temp; $i++) {
                $tableHTML .= '
                    <tr>
                        <td align="left">' . esc_html($sites[$i]['tour_stop']) . '</td>
                        <td align="left"><a target="blank_" href="../sea2Sky-site-page/?id=' . esc_html($sites[$i]['tour_stop']) . '">' . esc_html($sites[$i]['item_name']) . '</a></td>
                        <td align="left">' . esc_html($sites[$i]['item_type']) . '</td>
                        <td align="left">' . esc_html($sites[$i]['framework_concept']) . '</td>
                    </tr>
                ';
            }

            $tableHTML .= '
                </table>
            ';

        return $tableHTML;

    }

    add_shortcode('stk-list', 'seaToSky_list');

    /*
     Generates code for 'stk-map' shortcode, creating a map in a page
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_map() {
         wp_enqueue_script('seaToSky-script-4', 'https://www.google.com/jsapi', array(), 1, true );
         wp_enqueue_script('seaToSky-script-5', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyAogLQgkZED4Mv6uDZfb4XWpoFG63zUaZ0', array(), 1, true );

         $mapsQuery = "/js/seaToSky-maps.php?seaToSky_ft_address=" . get_option('seaToSky_ft_address') . "";
        $mapsQuery .= getMapQuery();

        wp_enqueue_script('seaToSky-script-6', plugins_url( $mapsQuery, __FILE__), array('seaToSky-script-4','seaToSky-script-5'), 1, true );

        return '<div id="googft-mapCanvas" style="width:100%; height:550px;"></div>';

    }

    add_shortcode('stk-map', 'seaToSky_map');

    /*
     * Helper function to pull the paramaters to be quried for map pins
     */
    function getMapQuery(){
        $jsonQuery = "";

        foreach($GLOBALS['dropdownCategories'] as $field){
            if(isset($_REQUEST[$field]) && $_REQUEST[$field] != ''){
                    $jsonQuery .= '&'.$field ."=".urlencode($_REQUEST[$field])."";
            }
        }
        
        return $jsonQuery;
    }

    /*
    Generates code for 'makefilter' shortcode, creating a filter box for quick searches
    @since     1.0.0
    @return    string  HTML
    */
    function seaToSky_filter() {

        $randInt = rand();

        $returnString = '<div class="seaToSky-filter">

            <div id="filter-left">
                <h3>Get Soil Sites: </h3>
            </div>

            <div id="filter-middle">
                <form name="filter" action="../results/?search=' . $randInt . '" method="get">
                    <select name="soil_order">
                        <option value="">Soil Order</option>
                        <option value="">---</option>
                        <option value="Brunisol">Brunisol</option>
                        <option value="Chernozem">Chernozem</option>
                        <option value="Cryosol">Cryosol</option>
                        <option value="Gleysol">Gleysol</option>
                        <option value="Luvisol">Luvisol</option>
                        <option value="Organic">Organic</option>
                        <option value="Podzol">Podzol</option>
                        <option value="Regosol">Regosol</option>
                        <option value="Solonetz">Solonetz</option>
                        <option value="Vertisol">Vertisol</option>
                    </select>

                    <select name="ecosystem">
                        <option value="">Ecosystem</option>
                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_ecosystems') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                    </select>

                    <select name="climate_zone">
                        <option value="">Climate Zone</option>
                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_climate_zones') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                    </select>

                    <select name="bc_biogeoclimatic_zone">
                        <option value="">BC Biogeoclimatic Zone</option>
                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_bc_biogeoclimatic_zones') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                    </select>
            </div>

            <div id="filter-right">
                    <input type="submit" value="Filter All Sites">
                    <input type="reset" value="Reset Filters" />
                </form>
            </div>
        </div>
        ';

        return $returnString;

    }

    add_shortcode('makefilter', 'seaToSky_filter');

    /*
    Generates code for search results, calling seaToSky_map and seaToSky_list
    @since     1.0.0
    */
    function seaToSky_results() {

        $seaToSkyFTResults = seaToSky_FT_query();

        if(sizeof($seaToSkyFTResults) != 2) {

            $map = seaToSky_map($seaToSkyFTResults);
            $list = seaToSky_list($seaToSkyFTResults);

            $completeResults = '<div id="seaToSky-tabs">
                    <div id="menu-wrap">
                        <ul>
                            <li><a href="#map">Mapped Results</a></li>
                            <li><a href="#list">Listed Results</a></li>
                        </ul>
                    </div>
                    <div class="seaToSky-tabs-pane" id="map">';

                        $completeResults .= $map;

                    $completeResults .= '</div>
                    <div class="seaToSky-tabs-pane" id="list">';

                        $completeResults .= $list;

                    $completeResults .= '</div>
                </div>
            ';

            return $completeResults;
        } else {
            return '<p>No results found</p>';
        }

    }
    add_shortcode('stk-results', 'seaToSky_results');

    /*
     Generates code for 'makesearch' shortcode, creating a search page
     @since     1.0.0
     @return    string  HTML
     */
    
    // !!!
    function seaToSky_search() {

        $seaToSkyFTResults = seaToSky_FT_general_query();
        $sites = getQueryData($seaToSkyFTResults);
        $numberOfSites = sizeof($sites);

        $returnString = 
        '
        <form name="seaToSky_search" style="text-align: left" action="../sea2sky-results/" method="GET" onload="fillCategory();">
            <div class="seaToSky-content-top">
                <div>
                    <h3 style="margin: 0px; margin-top: 10px; padding: 0px; text-align: center">Basic Site Search</h3>
                    <div style="text-align: center"> of ' . (sizeof($sites)-1) . ' SeaToSky sites </div>
                        <table>';

        foreach($seaToSkyFTResults['columns'] as $category){
            if(in_array($category, $GLOBALS['dropdownCategories']))
                $returnString .= getOptionsFromCategory($category, $numberOfSites, $sites);
        }

        // !!! edit the search params for key text searches
        $returnString .= '
                        </table>
                        <input type="submit" value="Search">
                        <input type="reset" style="margin-bottom: 10px;" value="Reset" />
                    </div>
                </div>

                <div class="seaToSky-content-top">
                <div>

                <div id="seaToSky-accordion-1">
                    <div class="seaToSky-accordion-title-1">
                        Location Search Criteria
                    </div>
                    <div class="seaToSky-accordion-pane-1">
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

    
        $returnString .= '
                        </table>
                    </div>
                </div>
            </div>
        </form>';

        /* !!! 
            - need to add search params for lat/long (number ranges)
            - need to also add go straight to site page
        */
        return $returnString;
    }

    add_shortcode('stk-search', 'seaToSky_search');

    /*
     *
     */
    function getOptionsFromCategory($category, $total, $sites){
            $tableHTML .= '
                <tr>
                    <td style="text-align: right">'.strtoupper(str_replace('_', ' ', $category)).' </td>
                <td>   
                    <select style="min-width: 300px; max-width: 300px;" name='.$category.'>
                    <option value="">...</option>';

            $options = [];
    
            for($i = 0; $i < $total; $i++){
                $option = $sites[$i][$category];
    
                if(!in_array($option, $options) && $option !== '')
                    array_push($options, $option);
            }

            foreach($options as $option) {     
                $tableHTML .= '
                <option value='.$option.'>'.$option.'</option>
            ';
            }

        $returnString .= $tableHTML.'
        </select>
        </td>
        </tr>';
        
        return $returnString;
    }

    /*
     Generates code for 'stk-site' shortcode, creating a soil site page
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_site() {

        if(isset($_REQUEST['id'])) {
            $id = $_REQUEST['id'];
        } else {
            $id = 0;
        }

        if($id) {
            $seaToSkyFTResults = seaToSky_FT_query();
        }

        if($id != 0) {
            if(sizeof($seaToSkyFTResults) != 2) {

                $selectedSite = getQueryData($seaToSkyFTResults)[0];

                // Title content
                $completeSite = getTitle($selectedSite);

                $completeSite .= getBasicFacts($selectedSite);

                // Media content
                $completeSite .= getMedia($selectedSite);

                $completeSite .= getDropDownDescriptions($selectedSite).'<br>';
                

            } else {
                $completeSite = '
                    <p>No valid site selected; please try again.</p>
                ';
            }
        } else {
            $completeSite = '
                <p>No valid site selected; please try again.</p>
            ';
        }

        return $completeSite;

    }
    add_shortcode('stk-site', 'seaToSky_site');

    /*
     * 
     */
    function getQueryData($seaToSkyFTResults){
        $data = array();
        $sites = $seaToSkyFTResults['rows'];
        $columnNames = $seaToSkyFTResults['columns'];

        $numRows = sizeof($sites);

        // Create an associative array so that columnName points to the row data for the individual site
        for($row = 0; $row < $numRows; $row++){
            $rowData = $sites[$row];
            $index = 0;

            foreach($columnNames as $column){
                $data[$row][$column] = $rowData[$index];
                $index++;
            }
        }

        return $data;
    }

    /* 
     * Helper functions for making an individual site 
     */
    function getTitle($selectedSite){
        return '<div class="seaToSky-content-top">
                <h3>'
                .esc_html($selectedSite["item_name"]).
                ', <span style="text-transform: uppercase">'
                .esc_html($selectedSite["item_type"]).
                '</span> 
                </h3>
                </div>';
    }

    function getBasicFacts($selectedSite){
      $facts =  '
        <div class="seaToSky-content-box">
            <h3><u>Basic Facts</u></h3>
            <p><strong>Focus is on: </strong> '
        .esc_html($selectedSite["framework_concept"]).
        '<br> <strong>Tour: </strong> '
        .esc_html($selectedSite["tour_name"]).
        ', <strong>Stop: </strong> '
        .esc_html($selectedSite["tour_stop"]).
        '<br> <strong>Location: </strong> '
        .esc_html($selectedSite["country"]).
        ', '
        .esc_html($selectedSite["region"]).
        ', '
        .esc_html($selectedSite["city"]).
        '<br> <strong>Latitude & Longitude: </strong>'
        .esc_html($selectedSite["latitude"]).
        ', '.esc_html($selectedSite["longitude"]).
        '<br> <strong>Elevation: </strong>'
        .esc_html($selectedSite["elevn"]).
         '<br> <strong>Brief Description: </strong> '
        .esc_html($selectedSite["short_description"]).
        '<br> <strong>Example: </strong>
            <a target="blank" href = '
        .esc_html($selectedSite["Google_URL"]).
        '> View on Google Maps </a>
        
        </p>
        </div>';

        return $facts;
    }

    function getDropDownDescriptions($selectedSite){
        return '<div id="seaToSky-accordion-1">
                        <div class="seaToSky-accordion-title-1">
                            Description
                        </div>
                        <div class="seaToSky-accordion-pane-1">'
                            .esc_html($selectedSite['long_description']).
                            '</p>
                        </div>
                    </div>

                    <div id="seaToSky-accordion-2">
                        <div class="seaToSky-accordion-title-2">
                            Affiliations and learning tasks
                        </div>
                        <div class="seaToSky-accordion-pane-1">
                            <p><strong>Submitted by: </strong>'
                            .esc_html($selectedSite["name_submit"]).
                            ', on '.esc_html($selectedSite["date_added"]).
                            '
                            <br>
                            <span style="margin-left: 3%">
                            <strong>Institutions: </strong>'
                            .esc_html($selectedSite["assoc_institutions"]).
                            '<br>
                            </span>

                            <span style="margin-left: 3%">
                            <strong> Courses: </strong>'
                            .esc_html($selectedSite['assoc_courses']).
                            '</span>

                            <br><br>
                            <strong>Study Questions: </strong> <br>'
                            .esc_html($selectedSite['study_questions']).
                            '</p>
                        </div>
                    </div>

                    <div id="seaToSky-accordion-3">
                        <div class="seaToSky-accordion-title-3">
                            Sources/references
                        </div>
                        <div class="seaToSky-accordion-pane-1">
                            <p>
                            <strong> <u>Source 1</u>: </strong>
                            <a style="color: blue" target="_blank" href=" '.esc_html($selectedSite['source1_url']).'" > Click Here </a>
                            <br> Type: '
                            .esc_html($selectedSite['source1_type']).
                            '<br> <i>'
                            .esc_html($selectedSite['source1_capt']).'</i>
                            </p>
                            <p>
                            <strong> <u>Source 2</u>: </strong>
                            <a style="color: blue" target="_blank" href=" '.esc_html($selectedSite['source2_url']).'" > Click Here </a>
                            <br> Type: '
                            .esc_html($selectedSite['source2_type']).
                            '<br> <i>'
                            .esc_html($selectedSite['source2_capt']).'</i>
                            </p>
                            <strong> <u>In-depth Source</u>: </strong>
                            <a style="color: blue" target="_blank" href=" '.esc_html($selectedSite['indepth_url']).'" > Click Here </a>
                            <br> Type: '
                            .esc_html($selectedSite['indepth_type']).
                            '<br> <i>'
                            .esc_html($selectedSite['indepth_capt']).'</i>
                            </p>
                            </p>
                            <strong> <u>Interactive Media</u>: </strong>
                            <a style="color: blue" href="../sea2sky-iframe/?id='.esc_html($selectedSite['tour_stop']).'" target="_blank"> 
                            Click Here </a>
                            <br> Caption: '
                            .esc_html($selectedSite['Holo_capt'])
                        .'</div>
                    </div>
                ';
    }

    function getDetails($selectedSite){
        return  '<div class="seaToSky-content-box">
            <h3><u>Details</u></h3>
            '.esc_html($selectedSite["long_description"]).
            '</div>';
    }

    function getSources($selectedSite){
        return 
        '<div class="seaToSky-content-box">
        <h3>Sources</h3>
        <p> INFO </p>
        </div>';
    }

    function getMedia($selectedSite){
        return '
        <div class="seaToSky-content-top">
            <img style="padding-top: 5%;" width=85% height=65% src='
        .esc_html($selectedSite['image1_url']).
        '> <br>
            <i> <div style="text-align: center; font-size: 15px;">'.esc_html($selectedSite['image1_capt']). 
            '</div> </i>
            <br>
        '.    
        '</div>
        </div>';
    }

    /*
     * Create an iframe on a new site given the site id 
     */
    function seaToSky_createIFrameSiteLink(){
        $iFrameSite = '';

        if(isset($_REQUEST['id'])) {
            $id = $_REQUEST['id'];
        } else {
            $id = 0;
        }

        if($id) {
            $jsonQuery = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+Holo_iframe+FROM+';

            $jsonQuery .= get_option('seaToSky_ft_address');

            $jsonQuery .= "+WHERE+tour_stop=".$id;
    
            $jsonQuery .= '&key=';
    
            $jsonQuery .= get_option('seaToSky_ft_key');
    
            $jsonData = file_get_contents($jsonQuery);
    
            $PHPdata = json_decode($jsonData);
    
            $seaToSkyFTResults = seaToSky_objectToArray($PHPdata);
        }

        if($id != 0) {
            if(sizeof($seaToSkyFTResults) != 2) {
                $iFrameSite = getQueryData($seaToSkyFTResults)[0];
                $completeSite = $iFrameSite['Holo_iframe'];
            }
            else {
                $completeSite = '
                    <p>No valid site provided; please try again.</p>
                ';
            }
        } else {
        $completeSite = '
            <p>No valid site provided; please try again.</p>
        ';
        }

        return $completeSite;
    }

    add_shortcode('stk-iframe', 'seaToSky_createIFrameSiteLink');


    /*
     Consumes a std object and makes from it a PHP array
     @since     1.0.0
     @return    array PHP array
     */

    function seaToSky_objectToArray($seaToSkyFTReturn) {
		if (is_object($seaToSkyFTReturn)) {
			// Gets the properties of the given object
			// with get_object_vars function
            $seaToSkyFTReturn = (array)$seaToSkyFTReturn;
		}

		if (is_array($seaToSkyFTReturn)) {
			/*
             * Return array converted to object
             * Using __FUNCTION__ (Magic constant)
             * for recursive call
             */
			return array_map(__FUNCTION__, $seaToSkyFTReturn);
		}
		else {
			// Return array
			return $seaToSkyFTReturn;
		}
	}

    /*
     Generates code for the Options/Admin page
     @since     1.0.0
     @return    n/a
     */

    function seaToSky_options_initializer() {

        add_settings_section('seaToSky_ft_data', '', 'seaToSky_ft_data_callback_functions', 'seaToSky-instruction-page');

        add_settings_field( 'seaToSky_ft_address', 'Fusion Table Address:', 'seaToSky_ft_address', 'seaToSky-instruction-page', 'seaToSky_ft_data');
        register_setting('seaToSky_ft_data', 'seaToSky_ft_address');
        add_settings_field( 'seaToSky_ft_key', 'Fusion Table Key:', 'seaToSky_ft_key', 'seaToSky-instruction-page', 'seaToSky_ft_data');
        register_setting('seaToSky_ft_data', 'seaToSky_ft_key');

        add_option( 'seaToSky_climate_zones', array());
        add_option( 'seaToSky_ecosystems', array());
        add_option( 'seaToSky_bc_biogeoclimatic_zones', array());
        add_option( 'seaToSky_diagnostic_soil_texture', array());
        add_option( 'seaToSky_parent_materials', array());
        add_option( 'seaToSky_soil_processes_groups', array());
        add_option( 'seaToSky_universities', array());
        add_option( 'seaToSky_courses', array());
    }

    add_action( 'admin_init', 'seaToSky_options_initializer');

    /*
     Generates code for Fusion Table address input
     @since     1.0.0
     @return    n/a
     */

    function seaToSky_ft_address(){
        echo '<input type="text" id="ft_address_id" name="seaToSky_ft_address" value="';
        echo get_option( 'seaToSky_ft_address');
        echo '" /><br />';
    }

    /*
     Generates code for Fusion Table key input
     @since     1.0.0
     @return    n/a
     */

    function seaToSky_ft_key(){
        echo '<input type="text" id="ft_key_id" name="seaToSky_ft_key" value="';
        echo get_option( 'seaToSky_ft_key');
        echo '" />';
    }

    function seaToSky_ft_data_callback_functions() {
    }

    /* function gmp_update_options() { // TODO: what was this for?
        if(isset($_POST['submit'])) {
        }
    } */

    function seaToSky_instructions_initializer() {
        if ( !current_user_can( 'manage_options' ) )
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

        if(isset($_POST['submit'])) {
            if ( !isset($_POST['seaToSky_nonce_field']) || !wp_verify_nonce($_POST['seaToSky_nonce_field'],'seaToSky_nonce_check') ) {
                print 'Sorry, your nonce did not verify.';
                exit;
            }
        }

        seaToSky_tabs();
        seaToSky_enqueue_style_1();

        if(isset($_REQUEST['ecosystem_to_add']) && $_REQUEST['ecosystem_to_add'] != '') {
            $tempText = $_REQUEST['ecosystem_to_add'];
            $tempArray = get_option('seaToSky_ecosystems');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_ecosystems', $tempArray);
        }
        if(isset($_REQUEST['ecosystem_to_delete']) && $_REQUEST['ecosystem_to_delete'] != '') {
            $tempText = $_REQUEST['ecosystem_to_delete'];
            $tempArray = get_option('seaToSky_ecosystems');
            unset($tempArray[$tempText]);
            update_option('seaToSky_ecosystems', $tempArray);
        }
        if(isset($_REQUEST['bc_biogeoclimatic_zone_to_add']) && $_REQUEST['bc_biogeoclimatic_zone_to_add'] != '') {
            $tempText = $_REQUEST['bc_biogeoclimatic_zone_to_add'];
            $tempArray = get_option('seaToSky_bc_biogeoclimatic_zones');
            $tempArray[$tempText] = $_REQUEST['bc_biogeoclimatic_zone_to_add'];
            update_option('seaToSky_bc_biogeoclimatic_zones', $tempArray);
        }
        if(isset($_REQUEST['bc_biogeoclimatic_zone_to_delete']) && $_REQUEST['bc_biogeoclimatic_zone_to_delete'] != '') {
            $tempText = $_REQUEST['bc_biogeoclimatic_zone_to_delete'];
            $tempArray = get_option('seaToSky_bc_biogeoclimatic_zones');
            unset($tempArray[$tempText]);
            update_option('seaToSky_bc_biogeoclimatic_zones', $tempArray);
        }
        if(isset($_REQUEST['climate_zone_to_add']) && $_REQUEST['climate_zone_to_add'] != '') {
            $tempText = $_REQUEST['climate_zone_to_add'];
            $tempArray = get_option('seaToSky_climate_zones');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_climate_zones', $tempArray);
        }
        if(isset($_REQUEST['climate_zone_to_delete']) && $_REQUEST['climate_zone_to_delete'] != '') {
            $tempText = $_REQUEST['climate_zone_to_delete'];
            $tempArray = get_option('seaToSky_climate_zones');
            unset($tempArray[$tempText]);
            update_option('seaToSky_climate_zones', $tempArray);
        }
        if(isset($_REQUEST['diagnostic_soil_texture_to_add']) && $_REQUEST['diagnostic_soil_texture_to_add'] != '') {
            $tempText = $_REQUEST['diagnostic_soil_texture_to_add'];
            $tempArray = get_option('seaToSky_diagnostic_soil_textures');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_diagnostic_soil_textures', $tempArray);
        }
        if(isset($_REQUEST['diagnostic_soil_texture_to_delete']) && $_REQUEST['diagnostic_soil_texture_to_delete'] != '') {
            $tempText = $_REQUEST['diagnostic_soil_texture_to_delete'];
            $tempArray = get_option('seaToSky_diagnostic_soil_textures');
            unset($tempArray[$tempText]);
            update_option('seaToSky_diagnostic_soil_textures', $tempArray);
        }
        if(isset($_REQUEST['parent_material_to_add']) && $_REQUEST['parent_material_to_add'] != '') {
            $tempText = $_REQUEST['parent_material_to_add'];
            $tempArray = get_option('seaToSky_parent_materials');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_parent_materials', $tempArray);
        }
        if(isset($_REQUEST['parent_material_to_delete']) && $_REQUEST['parent_material_to_delete'] != '') {
            $tempText = $_REQUEST['parent_material_to_delete'];
            $tempArray = get_option('seaToSky_parent_materials');
            unset($tempArray[$tempText]);
            update_option('seaToSky_parent_materials', $tempArray);
        }
        if(isset($_REQUEST['soil_processes_group_to_add']) && $_REQUEST['soil_processes_group_to_add'] != '') {
            $tempText = $_REQUEST['soil_processes_group_to_add'];
            $tempArray = get_option('seaToSky_soil_processes_groups');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_soil_processes_groups', $tempArray);
        }
        if(isset($_REQUEST['soil_processes_group_to_delete']) && $_REQUEST['soil_processes_group_to_delete'] != '') {
            $tempText = $_REQUEST['soil_processes_group_to_delete'];
            $tempArray = get_option('seaToSky_soil_processes_groups');
            unset($tempArray[$tempText]);
            update_option('seaToSky_soil_processes_groups', $tempArray);
        }
        if(isset($_REQUEST['seaToSky_universities_to_add']) && $_REQUEST['seaToSky_universities_to_add'] != '') {
            $tempText = $_REQUEST['seaToSky_universities_to_add'];
            $tempArray = get_option('seaToSky_universities');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_universities', $tempArray);
        }
        if(isset($_REQUEST['seaToSky_universities_to_delete']) && $_REQUEST['seaToSky_universities_to_delete'] != '') {
            $tempText = $_REQUEST['seaToSky_universities_to_delete'];
            $tempArray = get_option('seaToSky_universities');
            unset($tempArray[$tempText]);
            update_option('seaToSky_universities', $tempArray);
        }
        if(isset($_REQUEST['seaToSky_courses_to_add']) && $_REQUEST['seaToSky_courses_to_add'] != '') {
            $tempText = $_REQUEST['seaToSky_courses_to_add'];
            $tempArray = get_option('seaToSky_courses');
            $tempArray[$tempText] = $tempText;
            update_option('seaToSky_courses', $tempArray);
        }
        if(isset($_REQUEST['seaToSky_courses_to_delete']) && $_REQUEST['seaToSky_courses_to_delete'] != '') {
            $tempText = $_REQUEST['seaToSky_courses_to_delete'];
            $tempArray = get_option('seaToSky_courses');
            unset($tempArray[$tempText]);
            update_option('seaToSky_courses', $tempArray);
        }

        include 'seaToSky-instruction-maker.php';
    }

    function seaToSky_instructions_page() {
        add_menu_page( 'SeaToSky Instructions and Options', 'SeaToSky', 'manage_options', 'seaToSky-instruction-page', 'seaToSky_instructions_initializer' );
    }

    add_action( 'admin_menu', 'seaToSky_instructions_page' );

?>
