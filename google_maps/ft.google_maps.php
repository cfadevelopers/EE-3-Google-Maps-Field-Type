<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once PATH_THIRD . 'google_maps/config.php';

class Google_maps_ft extends EE_Fieldtype {

    var $info = array(
        'name'		=> 'Google Maps',
        'version'	=> '1.0'
    );

    var $google_api_key = GOOGLE_MAP_API_KEY;

    // --------------------------------------------------------------------

    /**
     * Display Field on Publish
     *
     * @access	public
     * @param	existing data
     * @return	field html
     *
     */
    function display_field($data)
    {
        $data_points = array('latitude', 'longitude', 'zoom');

        if ($data)
        {
            list($latitude, $longitude, $zoom) = explode('|', $data);
        }
        else
        {
            foreach($data_points as $key)
            {
                $$key = $this->settings[$key];
            }
        }

        $zoom = (int) $zoom;
        $options = compact($data_points);


        // Add script tags

        ee()->cp->add_to_head('<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $this->google_api_key . '"></script>');
        ee()->javascript->set_global('gmaps.'.$this->field_name.'.settings', $options);
        ee()->javascript->output('
			var fieldOpts = EE.gmaps.'.$this->field_name.'.settings,
				myLatlng = new google.maps.LatLng(fieldOpts.latitude || 51.507 , fieldOpts.longitude || 0.1278),
				myZoom = fieldOpts.zoom,
				geocoder = new google.maps.Geocoder(),
				hidden_field = $("#'.$this->field_name.'"),
				postcode_field = $("#postcode"),
				marker,
				map;

			var hidden_field_name = "'.$this->field_name.'";
			var hidden_field_split = hidden_field_name.split("_");
			var hidden_field_id = parseInt(hidden_field_split[2]);
    	    var latitude_field_id = hidden_field_id + 1; //latitude should be right after location otherwise it wont work
	        var longitude_field_id = hidden_field_id + 2; //longitude should be right after latitude otherwise it wont work

		    var latitude_field = $("input[name=field_id_" + latitude_field_id + "]" );
			var longitude_field = $("input[name=field_id_" + longitude_field_id + "]");

			var myOptions = {
				zoom: 5,
				center: myLatlng,
				scrollwheel: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};

			map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			update_postcode = function(location){
				   var latlng = new google.maps.LatLng(location.lat(), location.lng());

                   if(geocoder){
					geocoder.geocode({"latLng": location}, function(results, status) {
						if (status === "OK") {
							postcode_field.val(results[0].formatted_address);
						} else {
							console.log("Geocode was not successful for the following reason: " + status);
						}
					});
				}
			}

			google.maps.event.addListener(map, "click", function(event){
		   	    placeMarker(event.latLng);
			});
			google.maps.event.addListenerOnce(map, "idle", function(){
			 	placeMarker(myLatlng);
			});

			placeMarker = function(location) {
				  clearMarker();
			    marker = new google.maps.Marker({
			        position: location,
			        map: map
			    });
					hidden_field.val(location.lat()+"|"+location.lng()+"|"+map.getZoom());
					latitude_field.val(location.lat());
					longitude_field.val(location.lng());
					update_postcode(location);
			}

			clearMarker = function(){
				if(marker){
					marker.setMap(null);
				}
			}

			// change postcode event
			postcode_field.on("change paste blur", function(){
				geocodeAddress(geocoder,map);
			});

			geocodeAddress = function(geocoder, resultsMap) {
            var address = postcode_field.val();
            geocoder.geocode({"address": address}, function(results, status) {
                if (status === "OK") {
                    resultsMap.setCenter(results[0].geometry.location);
						resultsMap.setZoom(12);
						placeMarker(results[0].geometry.location);
                } else {
                    console.log("Geocode was not successful for the following reason: " + status);
                }
            });
      }


		');

        $value = implode('|', array_values($options));
        $hidden_input = form_input($this->field_name, $value, 'id="'.$this->field_name.'" style="display:none"');
        $postcode_input = form_input("postcode", "", 'id=postcode');
        return $hidden_input.$postcode_input.'<div style="height: 500px;"><div id="map_canvas" style="width: 100%; height: 100%"></div></div>';
    }


    /**
     * Replace tag
     *
     * @access	public
     * @param	field contents
     * @return	replacement text
     *
     */
    function replace_tag($data, $params = array(), $tagdata = FALSE)
    {
        static $script_on_page = FALSE;
        $ret = '';

        list($latitude, $longitude, $zoom) = explode('|', $data);

        if ( ! $script_on_page)
        {
            $ret .= '<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $this->google_api_key . '"></script>';
            $script_on_page = TRUE;
        }

        // this javascript is for demonstration purposes only
        // you should not assign window.onload directly

        $ret .= '<script type="text/javascript">
			function initialize() {
			    var latlng = new google.maps.LatLng('.$latitude.', '.$longitude.');
			    var myOptions = {
			      zoom: '.$zoom.',
			      center: latlng,
			      mapTypeId: google.maps.MapTypeId.ROADMAP
			    };
			    var map = new google.maps.Map(document.getElementById("map_canvas_'.$this->field_id.'"), myOptions);
			}
			window.onload = initialize;
			console.log("replace_tag");

		</script>';

        return $ret.'<div style="height: 500px;"><div id="map_canvas_'.$this->field_id.'" style="width: 100%; height: 100%"></div></div>';
    }

    // --------------------------------------------------------------------

    /**
     * Display Global Settings
     *
     * @access	public
     * @return	form contents
     *
     */
    function display_global_settings()
    {
        $val = array_merge($this->settings, $_POST);

        // Add script tags
        $this->_cp_js();
        ee()->javascript->output('$(window).load(gmaps);');

        $form = '';

        $form .= '<h3>Default Map</h3>';
        $form .= '<div style="height: 500px;"><div id="map_canvas" style="width: 100%; height: 100%"></div></div>';

        $form .= '<br /><h4>Manual Override</h4>';
        $form .= form_label('latitude', 'latitude').NBS.form_input('latitude', $val['latitude']).NBS.NBS.NBS.' ';
        $form .= form_label('longitude', 'longitude').NBS.form_input('longitude', $val['longitude']).NBS.NBS.NBS.' ';
        $form .= form_label('zoom', 'zoom').NBS.form_dropdown('zoom', range(1, 20), $val['zoom']);

        return $form;
    }

    // --------------------------------------------------------------------

    /**
     * Save Global Settings
     *
     * @access	public
     * @return	global settings
     *
     */
    function save_global_settings()
    {
        return array_merge($this->settings, $_POST);
    }

    // --------------------------------------------------------------------

    /**
     * Display Settings Screen
     *
     * @access	public
     * @return	default global settings
     *
     */
    function display_settings($data)
    {
        $latitude	= isset($data['latitude']) ? $data['latitude'] : $this->settings['latitude'];
        $longitude	= isset($data['longitude']) ? $data['longitude'] : $this->settings['longitude'];
        $zoom		= isset($data['zoom']) ? $data['zoom'] : $this->settings['zoom'];

        ee()->table->add_row(
            lang('latitude', 'latitude'),
            form_input('latitude', $latitude)
        );

        ee()->table->add_row(
            lang('longitude', 'longitude'),
            form_input('longitude', $longitude)
        );

        ee()->table->add_row(
            lang('zoom', 'zoom'),
            form_dropdown('zoom', range(1, 20), $zoom)
        );

        // Map preview
        $this->_cp_js();
        ee()->javascript->output(
        // Map container needs to be visible when you create
        // the map, so we'll wait for activate to fire once
            '$("#ft_google_maps").one("activate", gmaps);'
        );

        ee()->table->add_row(
            lang('preview'),
            '<div style="height: 300px;"><div id="map_canvas" style="width: 100%; height: 100%"></div></div>'
        );
    }

    // --------------------------------------------------------------------

    /**
     * Save Settings
     *
     * @access	public
     * @return	field settings
     *
     */
    function save_settings($data)
    {
        return array(
            'latitude'  => ee()->input->post('latitude'),
            'longitude' => ee()->input->post('longitude'),
            'zoom'      => ee()->input->post('zoom')
        );
    }

    // --------------------------------------------------------------------

    /**
     * Install Fieldtype
     *
     * @access	public
     * @return	default global settings
     *
     */
    function install()
    {
        return array(
            'latitude'	=> '51.5074',
            'longitude'	=> '0.1278',
            'zoom'		=> 5
        );
    }

    // --------------------------------------------------------------------

    /**
     * Control Panel Javascript
     *
     * @access	public
     * @return	void
     *
     */
    function _cp_js()
    {
        // This js is used on the global and regular settings
        // pages, but on the global screen the map takes up almost
        // the entire screen. So scroll wheel zooming becomes a hindrance.

        ee()->javascript->set_global('gmaps.scroll', ($_GET['C'] == 'content_admin'));

        ee()->cp->add_to_head('<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $this->google_api_key . '"></script>');
        ee()->cp->load_package_js('cp');
    }
}

/* End of file ft.google_maps.php */
/* Location: ./system/user/addons/google_maps/ft.google_maps.php */
