<?php
        
        $temp = 0;
    
        $jsonQuery = "leave_blank=''";
    
        if(isset($_REQUEST['id']) && $_REQUEST['id'] != '')
            $jsonQuery .= " AND id='" . $_REQUEST['id'] . "'";
        if(isset($_REQUEST['city']) && $_REQUEST['city'] != '')
            $jsonQuery .= " AND city CONTAINS IGNORING CASE '" . $_REQUEST['city'] . "'";
        if(isset($_REQUEST['region']) && $_REQUEST['region'] != '')
            $jsonQuery .= " AND planet CONTAINS IGNORING CASE '" . $_REQUEST['planet'] . "'";
        if(isset($_REQUEST['latitude']) && $_REQUEST['latitude'] != '') {
            $temp = 0;
            if(isset($_REQUEST['degrees']))
                $temp = $_REQUEST['degrees'];
            $minLat = $_REQUEST['latitude'] - $temp;
            if($minLat < -90) {
                $minLat = -90;
            }
            $maxLat = $_REQUEST['latitude'] + $temp;
            if($maxLat > 90) {
                $maxLat = 90;
            }
            $jsonQuery .= " AND latitude>=" . $minLat . "";
            $jsonQuery .= " AND latitude<=" . $maxLat . "";
        }
        if(isset($_REQUEST['longitude']) && $_REQUEST['longitude'] != '') {
            $temp = 0;
            if(isset($_REQUEST['degrees']))
                $temp = $_REQUEST['degrees'];
            $minLon = $_REQUEST['longitude'] - $temp;
            if($minLon < -180) {
                $minLon = -180;
            }
            $maxLon = $_REQUEST['longitude'] + $temp;
            if($maxLon > 180) {
                $maxLon = 180;
            }
            $jsonQuery .= " AND longitude>=" . $minLon . "";
            $jsonQuery .= " AND longitude<=" . $maxLon . "";
        }
        
        echo 'google.load(\'visualization\', \'1\', {\'packages\':[\'corechart\', \'table\', \'geomap\']});
                    
            var FT_TableID = "' . $_REQUEST['seaToSky_ft_address'] . '";
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
                        where: "' . $jsonQuery . '"
                    },
                    options: {
                        styleId: 2,
                        templateId: 2,
                    }
                });

                //SET MAP
                layer.setMap(map);

                var queryText = encodeURIComponent("SELECT \'latitude\', \'longitude\' FROM "+FT_TableID+" WHERE ' . $jsonQuery . '");
                var query = new google.visualization.Query(\'http://www.google.com/fusiontables/gvizdata?tq=\'  + queryText);
                                               
                                                                                                                        
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
                                 
?>