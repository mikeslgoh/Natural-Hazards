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

    add_shortcode('maketabs', 'seaToSky_tabs');

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
     Queries the Fusion Table, returning a std object
     @since     1.0.0
     @return    array  PHP array of results
     */

    function seaToSky_FT_query() {

        $jsonStart = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+*+FROM+';

        $jsonStart .= get_option('seaToSky_ft_address');

        $jsonQuery = "";

        if(isset($_REQUEST['id']) && $_REQUEST['id'] != '')
            $jsonQuery .= "key_id='" . urlencode($_REQUEST['id']) . "'";

        $jsonWhere = "+WHERE+";

        $jsonEnd = '&key=';

        $jsonEnd .= get_option('seaToSky_ft_key');

        $jsonStart .= $jsonWhere;

        $jsonStart .= $jsonQuery;

        $jsonStart .= $jsonEnd;

        $jsonData = file_get_contents($jsonStart);

        $PHPdata = json_decode($jsonData);

        $seaToSkyFTResults = seaToSky_objectToArray($PHPdata);

        return $seaToSkyFTResults;

    }

    /*
     Generates code for 'makelist' shortcode, creating a list of search results
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_list($seaToSkyFTResults) {

        $temp = sizeof($seaToSkyFTResults['rows']);

            // Make a table with a header for the information
            $tableHTML = '
                <table align="center" cellspacing="3" cellpadding="3" width="100%" style="margin:0px">
                    <tr>
                        <td align="left"><strong>ID</strong></td>
                        <td align="left"><strong>Site Name</strong></td>
                        <td align="left"><strong>Soil Order</strong></td>
                        <td align="left"><strong>Region</strong></td>
                    </tr>
            ';

            for($i = 0; $i < $temp; $i++) {
                $tableHTML .= '
                    <tr>
                        <td align="left">' . esc_html($seaToSkyFTResults['rows'][$i][1]) . '</td>
                        <td align="left"><a href="site/?id=' . esc_html($seaToSkyFTResults['rows'][$i][1]) . '">' . esc_html($seaToSkyFTResults['rows'][$i][2]) . '</a></td>
                        <td align="left">' . esc_html($seaToSkyFTResults['rows'][$i][16]) . '</td>
                        <td align="left">' . esc_html($seaToSkyFTResults['rows'][$i][14]) . '</td>
                    </tr>
                ';
            }

            $tableHTML .= '
                </table>
            ';

        return $tableHTML;

    }

    add_shortcode('makelist', 'seaToSky_list');

    /*
     Generates code for 'stk-map' shortcode, creating a map in a page
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_map() {
         wp_enqueue_script('seaToSky-script-4', 'http://www.google.com/jsapi', array(), 1, true );
         wp_enqueue_script('seaToSky-script-5', 'http://maps.googleapis.com/maps/api/js?key=AIzaSyAogLQgkZED4Mv6uDZfb4XWpoFG63zUaZ0', array(), 1, true );

         $mapsQuery = "/js/seaToSky-maps.php?seaToSky_ft_address=" . get_option('seaToSky_ft_address') . "";

        wp_enqueue_script('seaToSky-script-6', plugins_url( $mapsQuery, __FILE__), array('seaToSky-script-4','seaToSky-script-5'), 1, true );

        return '<div id="googft-mapCanvas" style="width:100%; height:550px;"></div>';

    }

    add_shortcode('stk-map', 'seaToSky_map');

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
    add_shortcode('makeresults', 'seaToSky_results');

    /*
     Generates code for 'makesearch' shortcode, creating a search page
     @since     1.0.0
     @return    string  HTML
     */

    function seaToSky_search() {

        $jsonStart = 'https://www.googleapis.com/fusiontables/v1/query?sql=SELECT+id+FROM+';

        $jsonStart .= get_option('seaToSky_ft_address');

        $jsonStart .= '&key=';

        $jsonStart .= get_option('seaToSky_ft_key');

        $jsonData = file_get_contents($jsonStart);

        $PHPdata = json_decode($jsonData);

        $seaToSkyFTResults = seaToSky_objectToArray($PHPdata);

        $returnString = '
            <form name="seaToSky_search" style="text-align: center" action="../results/" metho="GET" onload="fillCategory();">
                <div class="seaToSky-search-left">
                    <div class="seaToSky-search-box">
                        <h3 style="margin: 0px; margin-top: 10px; padding: 0px; text-align: center">Basic Soil Search</h3>
                        of ' . sizeof($seaToSkyFTResults['rows']) . ' SeaToSky sites
                        <table>
                            <tr>
                                <td>
                                    Soil Order:
                                </td>
                                <td>
                                    <select  name="soil_order" onChange="seaToSky_select_great_group();" >
                                        <option value="">Soil Order</option>
                                    </select
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Great Group:
                                </td>
                                <td>

                                    <select id="great_group" name="great_group" onChange="seaToSky_select_subgroup()">
                                        <option value="">Select a Soil Order first</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Subgroup:
                                </td>
                                <td>
                                    <select id="subgroup" name="subgroup"">
                                        <option value="">Select a Great Group first</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"><div style="color: #aaa">To select a Great Group, select a Soil Order. To select a Subgroup, select a Great Group. NOTE: Not all soil sites have a Subgroup listed!</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Ecosystem:
                                </td>
                                <td>
                                    <select name="ecosystem">
                                        <option value="">Ecosystem</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_ecosystems') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    BC Biogeoclimatic Zone:
                                </td>
                                <td>
                                    <select name="bc_biogeoclimatic_zone">
                                        <option value="">BC Biogeoclimatic Zone</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_bc_biogeoclimatic_zones') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Climate Zone:
                                </td>
                                <td>
                                    <select name="climate_zone">
                                        <option value="">Climate Zone</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_climate_zones') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Search">
                        <input type="reset" style="margin-bottom: 10px;" value="Reset" />
                    </div>
                <div id="seaToSky-accordion-1">
                    <div class="seaToSky-accordion-title-1">
                        Advanced Soil Search Criteria
                    </div>
                    <div class="seaToSky-accordion-pane-1">
                        <table>
                            <tr>
                                <td>
                                    Diagnostic Soil Texture:
                                </td>
                                <td>
                                    <select name="soil_texture_diag">
                                        <option value="">Diagnostic Soil Texture</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_diagnostic_soil_textures') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Parent Material:
                                </td>
                                <td>
                                    <select name="parent_material">
                                        <option value="">Parent Material</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_parent_materials') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Soil Processes Group:
                                </td>
                                <td>
                                    <select name="primary_soil_process_gro">
                                        <option value="">Soil Process Group</option>
                                        <option value="">---</option>
        ';

        foreach(get_option('seaToSky_soil_processes_groups') as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div id="seaToSky-accordion-4">
                    <div class="seaToSky-accordion-title-4">
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
                <div id="seaToSky-accordion-7">
                    <div class="seaToSky-accordion-title-7">
                        Sources and Users Search Criteria
                    </div>
                    <div class="seaToSky-accordion-pane-2">
                        <table>
                            <tr>
                                <td>
                                    Associated Course (keyword):
                                </td>
                                <td>
                                    <select name="courses">
                                        <option value="">Courses</option>
                                        <option value="">---</option>
        ';

        $tempArray = get_option('seaToSky_courses');
        sort($tempArray);
        foreach($tempArray as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Associated University/Organization (keyword):
                                </td>
                                <td>
                                    <select name="universities">
                                        <option value="">Universities/Organizations</option>
                                        <option value="">---</option>
        ';

        $tempArray = get_option('seaToSky_universities');
        sort($tempArray);
        foreach($tempArray as $tempValue) {
            $returnString .= '<option value="' . $tempValue . '">' . $tempValue . '</option>';
        }

        $returnString .= '
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </form>
        <div class="seaToSky-search-right">
            <div class="seaToSky-search-box">
                <p >The search page allows you to search and filter <strong>SeaToSky</strong> sites. Feel that only Cryosols are cool? Have a yearn-ozem for some Chernozem? Want to see only Podzols within 2&#176 of 49&#176 latitude used by UBC course APBI100? The search page can make all your dreams come true - just click \'Search\' when you\'re ready.</p>
                <p>Basic search criteria are on the left. Click the panes below them for additional search options. <strong>Be warned, though!</strong> Too many search terms will limit your results, for not all soil sites have information for all fields. For hints on creating and sharing useful searches, go to the <a href="../help">Help page</a>.</p>
                <p>If you already know the <strong>SeaToSky</strong> number of the site you want to see, enter it below and click \'Go to Site\'.</p>
                <form name="site" style="text-align: center" action="../site/">
                    <input type="number" name="id" min="0" style="width:50px">
                    <input type="submit" style="margin-bottom:10px" value="Go to Site">
                </form>
            </div>
        </div>
        ';

        return $returnString;
    }

    add_shortcode('makesearch', 'seaToSky_search');

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

                $selectedSite = $seaToSkyFTResults['rows'][0];

                $completeSite = '
                    <div class="seaToSky-content-top"><h3>Site '
                    .esc_html($selectedSite[1]).
                    ': '
                    .esc_html($selectedSite[2])
                ;

                $completeSite .= '
                    </div>
                ';


                $completeSite .= '
                    <div class="seaToSky-site-left">
                    <div class="seaToSky-content-box">
                        <h3>Basic Facts<h3>
                        ...
                    </div>
                ';

                $completeSite .= '
                    <div id="seaToSky-accordion-1">
                        <div class="seaToSky-accordion-title-1">
                            INFO 1
                        </div>
                        <div class="seaToSky-accordion-pane-1">
                            <p><strong>Soil Order: </strong>'
                            .esc_html($selectedSite[16]).
                            '<br /><strong>Great Group: </strong>'
                            .esc_html($selectedSite[17]).
                            '</p>
                        </div>
                    </div>

                    <div id="seaToSky-accordion-2">
                        <div class="seaToSky-accordion-title-2">
                            INFO 2
                        </div>
                        <div class="seaToSky-accordion-pane-1">
                            <p><strong>Land Form: </strong>'
                            .esc_html($selectedSite[27]).
                            '<br /><strong>Parent Material: </strong>'
                            .esc_html($selectedSite[28]).
                            '<br /><strong>Elevation (m): </strong>'
                            .esc_html($selectedSite[26]).
                            '<br /><strong>Topography: </strong>'
                            .esc_html($selectedSite[29]).
                            '<br /><strong>Affected by Glaciation: </strong>'
                            .esc_html($selectedSite[31]).
                            '</p>
                        </div>
                    </div>
                ';

                $completeSite .= '</div>
                    <div class="seaToSky-site-right">
                    <div class="seaToSky-content-media">
                    <h3>Media</h3>
                ';

                // If there is a video that wants to be inserted
                if(esc_url($selectedSite[66]) != "") {
                    $completeSite .= '
                        <iframe width="100%" height="300" src="'
                        . esc_url($selectedSite[66]) .
                        '" frameborder="0" allowfullscreen></iframe>
                    ';
                    if(esc_html($selectedSite[67]) != "" || esc_html($selectedSite[68]) != "") {
                        $completeSite .= '
                            <p style="text-align: center">
                        ';
                        if(esc_html($selectedSite[67]) != "") {
                            $completeSite .= '
                                Featured expert: '
                                . esc_html($selectedSite[67])
                            ;
                            if(esc_html($selectedSite[68]) != "") {
                                $completeSite .= '<br />';                        ;
                        }
                        }
                        if(esc_html($selectedSite[68]) != "") {
                            $completeSite .= '
                                Video host: '
                                . esc_html($selectedSite[68])
                            ;
                        }
                        $completeSite .= '
                            </p>
                        ';
                    }
                }
                if(esc_url($selectedSite[70]) != "") {
                    $completeSite .= '
                        <img width="100%" src="'
                        . esc_url($selectedSite[70]) .
                        '" alt="'
                        . $selectedSite[69] .
                        '">
                    ';
                    if(esc_html($selectedSite[69]) != "") {
                        $completeSite .= '
                            <p style="text-align: center">'
                            . esc_html($selectedSite[69]) .
                            '</p>
                        ';
                    }
                }
                if(esc_url($selectedSite[72]) != "") {
                    $completeSite .= '
                        <img width="100%" src="'
                        . esc_url($selectedSite[72]) .
                        '" alt="'
                        . $selectedSite[71] .
                        '">
                    ';
                    if(esc_html($selectedSite[71]) != "") {
                        $completeSite .= '
                            <p style="text-align: center">'
                            . esc_html($selectedSite[71]) .
                            '</p>
                        ';
                    }
                }
                if(esc_url($selectedSite[74]) != "") {
                    $completeSite .= '
                        <img width="100%" src="'
                        . esc_url($selectedSite[74]) .
                        '" alt="'
                        . $selectedSite[73] .
                        '">
                    ';
                    if(esc_html($selectedSite[73]) != "") {
                        $completeSite .= '
                            <p style="text-align: center">'
                            . esc_html($selectedSite[73]) .
                            '</p>
                        ';
                    }
                }
                if(esc_url($selectedSite[76]) != "") {
                    $completeSite .= '
                        <img width="100%" src="'
                        . esc_url($selectedSite[76]) .
                        '" alt="'
                        . $selectedSite[75] .
                        '">
                    ';
                    if(esc_html($selectedSite[75]) != "") {
                        $completeSite .= '
                            <p style="text-align: center">'
                            . esc_html($selectedSite[75]) .
                            '</p>
                        ';
                    }
                }
                if(esc_url($selectedSite[66]) == "" && esc_url($selectedSite[70] && esc_url($selectedSite[72]) == "" && esc_url($selectedSite[74]) == "" && esc_url($selectedSite[76]) == "") == "") {
                    $completeSite .= '
                        <img width="100%" src="http://ar-seaToSky.sites.olt.ubc.ca/files/2013/07/oops.png" alt="No media found">
                    ';
                }

                $completeSite .= '</div>
                    <div class="seaToSky-content-links">
                    <h3>Links</h3>
                ';

                // Insert external links here
                if(esc_url($selectedSite[53]) != '' || esc_url($selectedSite[55]) != '' || esc_url($selectedSite[57]) != '' || esc_url($selectedSite[59]) != '' || esc_url($selectedSite[61]) != '' || esc_url($selectedSite[63]) != '') {

                    $completeSite .= '
                        <p>
                    ';

                    if(esc_url($selectedSite[53]) != '') {
                        $completeSite .= '
                            <strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[53]) .
                            '">'
                            . esc_html($selectedSite[52]) .
                            '</a>
                        ';
                    }
                    if(esc_url($selectedSite[55]) != '') {
                        $completeSite .= '
                            <br /><strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[55]) .
                            '">'
                            . esc_html($selectedSite[54]) .
                            '</a>
                        ';
                    }
                    if(esc_url($selectedSite[57]) != '') {
                        $completeSite .= '
                            <br /><strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[57]) .
                            '">'
                            . esc_html($selectedSite[56]) .
                            '</a>
                        ';
                    }
                    if(esc_url($selectedSite[59]) != '') {
                        $completeSite .= '
                            <br /><strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[59]) .
                            '">'
                            . esc_html($selectedSite[58]) .
                            '</a>
                        ';
                    }
                    if(esc_url($selectedSite[61]) != '') {
                        $completeSite .= '
                            <br /><strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[61]) .
                            '">'
                            . $selectedSite[60] .
                            '</a>
                        ';
                    }
                    if(esc_url($selectedSite[63]) != '') {
                        $completeSite .= '
                            <br /><strong>External Page:</strong> <a href="'
                            . esc_url($selectedSite[63]) .
                            '">'
                            . esc_html($selectedSite[62]) .
                            '</a>
                        ';
                    }


                    $completeSite .= '
                        </p>
                    ';

                }

                $completeSite .= '
                    </div>
                    </div>
                ';

            } else {
                $completeSite = '
                    <p>No valid soil site selected; please try again.</p>
                ';
            }
        } else {
            $completeSite = '
                <p>No valid soil site selected; please try again.</p>
            ';
        }

        return $completeSite;

    }
    add_shortcode('stk-site', 'seaToSky_site');

    /*
     Consumes a std object and makes from it a PHP array
     @since     1.0.0
     @return    array PHP array
     */

    function seaToSky_objectToArray($seaToSkyFTReturn) {
		if (is_object($seaToSkyFTReturn)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$seaToSkyFTReturn = get_object_vars($seaToSkyFTReturn);
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
