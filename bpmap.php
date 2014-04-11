<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgContentBlueProMap extends JPlugin {

	private $_trim_chars = " \t\n\r\0\x0B\"";

	private function _getMapPlaces($text) {
		$matches = array();
		if (preg_match_all('~{map\s*(.*?)}(([^{]*){/map})*~si', $text, $matches, PREG_SET_ORDER)) {
			return $matches;
		} else {
			return false;
		}
	}
	
	private function _getAPIURL() {
		$url = 'https://maps.googleapis.com/maps/api/js?sensor=true';
		$api_key = $this->params->get('api_key', '');
		if ($api_key) {
			$url .= "&key=$api_key";
		}
	}
	
	private function _getMapParams($param_string) {
		// {map zoom="14"}[x;y][x;y]{/map}
		
		$map_params = array();
		$map_params['latitude'] = floatval($this->params->get('latitude'));
		$map_params['longitude'] = floatval($this->params->get('longitude'));
		$map_params['zoom'] = intval($this->params->get('zoom', '14'));
		$map_params['type'] = $this->params->get('type', 'G_NORMAL_MAP');
		$map_params['type_control'] = (bool) $this->params->get('type_control', '1');
		$map_params['controll'] = $this->params->get('controll', 'small');
		$map_params['marker'] = (bool) $this->params->get('marker', '1');
		$map_params['draging'] = (bool) $this->params->get('draging', '1');
		$map_params['mousewheel'] = (bool) $this->params->get('mousewheel', '1');
		$map_params['width'] = $this->params->get('width');
		$map_params['height'] = $this->params->get('height', '300px');
		$map_params['markers'] = array();

		if (!empty($param_string[1])) {
			$article_params = explode('" ', $param_string[1]);
			foreach ($article_params as $param) {
				list($name, $value) = explode('="', $param);
				switch (trim($name, $this->_trim_chars)) {
					case 'latitude':
						$map_params['latitude'] = floatval($value);
						break;
					case 'longitude':
						$map_params['longitude'] = floatval($value);
						break;
					case 'zoom':
						$map_params['zoom'] = intval($value);
						break;
					case 'type':
						$map_params['type'] = trim($value, $this->_trim_chars);
						break;
					case 'type_control':
						$map_params['type_control'] = (bool) $value;
						break;
					case 'controll':
						$map_params['controll'] = trim($value, $this->_trim_chars);
						break;
					case 'marker':
						$map_params['marker'] = (bool) $value;
						break;
					case 'draging':
						$map_params['draging'] = (bool) $value;
						break;
					case 'mousewheel':
						$map_params['mousewheel'] = (bool) $value;
						break;
					case 'width':
						$map_params['width'] = $value;
						break;
					case 'height':
						$map_params['height'] = $value;
						break;
				}
			}
		}

		if (!empty($param_string[3])) {
			$markers = explode('][', $param_string[3]);
			foreach ($markers as $marker) {
				$marker = trim($marker, '[]');
				list($latitude, $longitude) = explode(';', $marker);
				$map_params['markers'][] = array(
						'latitude' => $latitude,
						'longitude' => $longitude);
			}
		}

		return $map_params;
	}
	
	private function _getMapInitScript($script) 
		$init_script = "function mapInit() {\n";
		$init_script .= implode("\n", $script);
		$init_script .= "\n}\ngoogle.maps.event.addDomListener(window, 'load', mapInit);";
		return $init_script;
	}
	
	private function _getMapHTML($params, $index) {
		$style = '';
		if ($params['width']) {
			$style = sprintf('width: %s;', $params['width']);
		}
		if ($params['height']) {
			$style .= sprintf('height: %s;', $params['height']);
		}
		if ($style) {
			$style = sprintf(' style="%s"', $style);
		}
		
		return sprintf('<div class="bpmap" id="bpmap_%d"%s></div>', $index, $style);
	}
	
	private function _getMapSettingsScript($params, $index) {
		
				switch ($user_params['controll']) {
					case 'small':
						$script .= sprintf("map_%d.addControl(new GSmallMapControl());\n", $index);
						break;
					case 'large':
						$script .= sprintf("map_%d.addControl(new GLargeMapControl());\n", $index);
						break;
				}
				if ($user_params['draging']) {
					$script .= sprintf("map_%d.enableDragging();\n", $index);
				} else {
					$script .= sprintf("map_%d.disableDragging();\n", $index);
				}
				if ($user_params['mousewheel']) {
					$script .= sprintf("map_%d.enableScrollWheelZoom();\n", $index);
				} else {
					$script .= sprintf("map_%d.disableScrollWheelZoom();\n", $index);
				}
	}
	
	public function onPrepareContent(&$article, &$params, $limitstart) {
		$map_places = $this->_getMapPlaces($article-text);
		
		if ($map_places) {
			$document = JFactory::getDocument();
			$document->addScript($this->_getAPIURL());
			
			$script = array();
			$html = array();
			foreach ($map_places as $index => $place) {
				$map_params = $this->_getMapParams($place);
				$script[] = $this->_getMapSettingsScript($map_params, $index);
				$html[$place[0]] = $this->_getMapHTML($map_params, $index);
			}
			
			$document->addScriptDeclaration($this->_getMapInitScript($script));
			$article->text = strtr($article->text, $html);
		}
	}

}
?>
		<script type="text/javascript">
			function initialize() {
				var myLatlng = new google.maps.LatLng(50.0241817, 14.5259175);
				var mapOptions = {
						center: myLatlng,
						zoom: 14,
						mapTypeId: google.maps.MapTypeId.ROADMAP
				};
				var map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
				var marker = new google.maps.Marker({
		    	  position: myLatlng,
						map: map,
						title: 'Tady bydlím',
						animation: google.maps.Animation.BOUNCE
				});
			}
		google.maps.event.addDomListener(window, 'load', initialize);
		</script>