<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Import_Database', false ) ) :

	class VP_Woo_Pont_Import_Database {
		private static $skip_check = false;

		public static function get_pont_types() {
			return apply_filters('vp_woo_pont_import_database_providers', array('foxpost', 'postapont', 'packeta', 'sprinter', 'expressone', 'gls', 'dpd', 'sameday', 'kvikk'));
		}

		public static function init() {

			//Setup daily scheduled action to refresh databases
			foreach (self::get_pont_types() as $pont_type) {
				if($pont_type == 'kvikk') {
					//
				} else {
					add_action( 'vp_woo_pont_update_'.$pont_type.'_list', array( __CLASS__, 'get_'.$pont_type.'_json' ) );
				}
			}

			//Ajax function to trigger import manually
			add_action( 'wp_ajax_vp_woo_pont_import_json_manually', array( __CLASS__, 'import_manually' ) );

		}

		public static function schedule_actions($frequency = false) {

			foreach (self::get_pont_types() as $pont_type) {
				WC()->queue()->cancel_all( 'vp_woo_pont_update_'.$pont_type.'_list' );
			}

			foreach (self::get_pont_types() as $pont_type) {

				//Download postapont list on activation
				WC()->queue()->add( 'vp_woo_pont_update_'.$pont_type.'_list', array(), 'vp_woo_pont' );

				//Get saved frequency value
				$hours = VP_Woo_Pont_Helpers::get_option('import_frequency', 24);
				if($frequency) {
					$hours = $frequency;
				}

				//Setup scheduled actions
				WC()->queue()->schedule_recurring( time()+HOUR_IN_SECONDS*$hours, HOUR_IN_SECONDS*$hours, 'vp_woo_pont_update_'.$pont_type.'_list', array(), 'vp_woo_pont' );

			}
		}

		public static function get_postapont_json() {
			$need_sync = self::check_if_sync_needed('postapont');
			if(!$need_sync) return false;

			$request = wp_remote_get('https://www.posta.hu/szolgaltatasok/posta-srv-postoffice/rest/postoffice/listPPMarkers?callback');

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'postapont-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Remove first and last character, because it was a jsonp request
			$body = substr($body, 1, -1);

			//Try to convert into json
			$json = json_decode( $body );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Create a new json
			$results = array();
			$valid_groups = array(10 => 'posta',20 => 'postapont',30 => 'automata',50 => 'postapont',70 => 'postapont'); //60 and 80 category is available, but not documented, so lets skip those for now

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $postapont) {

				//Skip empty coordinates
				if($postapont->lat == null || $postapont->address == null) {
					continue;
				}

				$group = intval(substr($postapont->group, 0, 2));
				if(in_array($group, array_keys($valid_groups))) {
					$result = array(
						'id' => $postapont->id,
						'group' => $group,
						'lat' => number_format($postapont->lat, 5, '.', ''),
						'lon' => number_format($postapont->lon, 5, '.', ''),
						'name' => $postapont->name,
						'zip' => $postapont->zip,
						'addr' => $postapont->address,
						'city' => $postapont->county,
						'comment' => $postapont->phone
					);

					//If there is a separate zip code, use it for search
					if($postapont->zip != $postapont->kzip) {
						$result['keywords'] = $postapont->kzip;
					}

					//Grouped by groups
					$group_id = $valid_groups[$group];
					if(!isset($results[$group_id])) {
						$results[$group_id] = array();
					}

					$results[$group_id][] = $result;
				}
			}

			//Save as separate files
			$saved_files = array();
			foreach ($results as $group_id => $points) {
				$saved_files['postapont_'.$group_id] = self::save_json_file('postapont_'.$group_id, $points);
			}

			return $saved_files;
		}
		
		public static function get_foxpost_json() {
			$need_sync = self::check_if_sync_needed('foxpost');
			if(!$need_sync) return false;

			$request = wp_remote_get('https://cdn.foxpost.hu/foxpost_terminals_extended_v3.json');

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'foxpost-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Create a new json
			$results = array();
			$open_days = array('hetfo', 'kedd', 'szerda', 'csutortok', 'pentek', 'szombat', 'vasarnap');

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $foxpost) {
				$result = array(
					'id' => $foxpost['place_id'],
					'lat' => number_format($foxpost['geolat'], 5, '.', ''),
					'lon' => number_format($foxpost['geolng'], 5, '.', ''),
					'name' => $foxpost['name'],
					'zip' => $foxpost['zip'],
					'addr' => $foxpost['street'],
					'city' => $foxpost['city'],
					'comment' => $foxpost['findme']
				);

				//Open hours
				if(isset($foxpost['open'])) {
					$result['hours'] = array();
					foreach ($open_days as $day => $day_name) {
						if(isset($foxpost['open'][$day_name])) {
							$result['hours'][$day+1] = $foxpost['open'][$day_name];
						}
					}
				}

				//Check if its the same all week(so the json is smaller)
				$open_days_count = array_unique($result['hours']);
				if(count($result['hours']) == 7 && count($open_days_count) == 1) {
					$times = $result['hours'][1];
					$result['hours'] = $times;
				}

				$results[] = $result;
			}

			//Save stuff
			$saved_files = array();
			$saved_files['foxpost'] = self::save_json_file('foxpost', $results);
			return $saved_files;

		}

		public static function get_packeta_json($api_key = false) {
			$need_sync = self::check_if_sync_needed('packeta');
			if(!$need_sync) return false;

			//Check API key
			if(!$api_key) {
				$api_key = VP_Woo_Pont_Helpers::get_option('packeta_api_key');
			}

			//If theres no api key, return error
			if(!$api_key) {
				return false;
			}

			//Get supported countries
			$enabled_countries = get_option('vp_woo_pont_packeta_countries', array('HU'));

			//Download address delivery list too
			$download_folder = VP_Woo_Pont_Helpers::get_download_folder('packeta');
			$file_points = $download_folder['dir'].'packeta-points-source-file.json';

			//Create a new json
			$results = array('zbox' => array(), 'shop' => array(), 'mpl_postapont' => array(), 'mpl_automata' => array());
			$open_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

			//For packeta branded pickup points
			if(in_array('HU', $enabled_countries) || in_array('RO', $enabled_countries) || in_array('SK', $enabled_countries) || in_array('CZ', $enabled_countries) || in_array('MPL_POSTAPONT', $enabled_countries) || in_array('MPL_AUTOMATA', $enabled_countries)) {

				//Make request for branches json file
				$request = wp_remote_get('https://points-api.kvikk.hu/points?search=packeta,mpl&country=HU,SK,CZ,RO', array(
					'timeout' => 100,
				));

				//Check for errors
				if( is_wp_error( $request ) ) {
					VP_Woo_Pont()->log_error_messages($request, 'packeta-import-points');
					return false;
				}

				//Get body
				$body = wp_remote_retrieve_body( $request );

				//Try to convert into json
				$json = json_decode( $body, true );

				//Check if json exists
				if($json === null) {
					return false;
				}

				//Simplify json, so its smaller to store, faster to load
				foreach ($json['data'] as $place) {

					//For now, only hungarian points
					if(!in_array($place['country'], $enabled_countries)) {
						continue;
					}

					//Create results
					$result = $place;
					$type = $result['type'];
					unset($result['type']);

					//Check if we need to skip Z-Box
					if($type == 'zbox') {
						$results['zbox'][] = $result;
					} elseif($type == 'zpont') {
						$results['shop'][] = $result;
					} elseif($type == 'postapont' || $type == 'posta') {
						$results['mpl_postapont'][] = $result;
					} elseif($type == 'automata') {
						$results['mpl_automata'][] = $result;
					}

				}

			}

			//Extra points for the rest of the countries
			$extra_point_ids = array();
			foreach ($enabled_countries as $carrier_id) {
				if(intval($carrier_id) > 0) {
					$extra_point_ids[] = $carrier_id;
				}
			}

			//Download extra pickup points
			if(!empty($extra_point_ids)) {
				$request = wp_remote_get('https://www.zasilkovna.cz/api/'.$api_key.'/point.json?ids='.implode(',', $extra_point_ids), array(
					'timeout' => 100,
					'stream' => true,
					'filename' => $file_points
				));

				//Check for errors
				if( is_wp_error( $request ) ) {
					VP_Woo_Pont()->log_error_messages($request, 'packeta-import-points');
					return false;
				}

				//Get body
				$points = file_get_contents($file_points);

				//Try to convert into json
				$json = json_decode( $points );

				//Check if json exists
				if($json === null) {
					return false;
				}

				foreach ($json->carriers as $json_carrier) {
					foreach ($json_carrier->points as $place) {
						if($place->displayFrontend == '1') {
							$result = array(
								'id' => $place->code,
								'lat' => number_format($place->coordinates->latitude, 5, '.', ''),
								'lon' => number_format($place->coordinates->longitude, 5, '.', ''),
								'name' => $place->city.' '.$place->street,
								'zip' => $place->zip,
								'addr' => $place->street.' '.$place->streetNumber,
								'city' => $place->city,
								'country' => strtoupper($place->country),
								'carrier' => $json_carrier->id
							);
							$results['shop'][] = $result;
						}
					}
				}

				//Delete source files
				unlink($file_points);

			}

			//Save stuff
			$saved_files = array();
			foreach ($results as $type => $points) {
				$saved_files['packeta_'.$type] = self::save_json_file('packeta_'.$type, $points);
			}


			return $saved_files;
		}

		public static function get_sprinter_json() {
			$need_sync = self::check_if_sync_needed('sprinter');
			if(!$need_sync) return false;

			//Make request for json file
			$request = wp_remote_get('https://partner.pickpackpont.hu/stores/ShopList.json');

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'sprinter-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Remove BOM, so its a valid json
			$bom = pack('H*','EFBBBF');
	    	$body = preg_replace("/^$bom/", '', $body);

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Create a new json
			$results = array();
			$open_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $place) {

				//Check for valid coordinates
				if(empty($place['lat'])) continue;

				//Parse address
				$address = explode(', ', $place['address']);
				
				//For now, only hungarian points
				$result = array(
					'id' => $place['shopCode'],
					'lat' => number_format($place['lat'], 5, '.', ''),
					'lon' => number_format($place['lng'], 5, '.', ''),
					'name' => $place['shopName'],
					'zip' => $place['zipCode'],
					'addr' => $address[2],
					'city' => $place['city']
				);

				if($place['description']) {
					$result['comment'] = $place['description'];
				}

				//Open hours
				if(isset($place['openTimes'])) {
					$result['hours'] = array();
					foreach ($open_days as $day => $day_name) {
						if(isset($place['openTimes'][$day_name])) {
							if($place['openTimes'][$day_name]['isOpen'] == 'true') {
								$result['hours'][$day+1] = $place['openTimes'][$day_name]['from'].' - '.$place['openTimes'][$day_name]['to'];
							} else {
								$result['hours'][$day+1] = false;
							}
						}
					}
				}

				//Check if its 0-24 all week(so the json is smaller)
				$open_days_count = array_unique($result['hours']);
				if(count($result['hours']) == 7 && count($open_days_count) == 1) {
					$times = $result['hours'][1];
					$result['hours'] = $times;
				}

				$results[] = $result;
			}

			//Save stuff
			$saved_files = array();
			$saved_files['sprinter'] = self::save_json_file('sprinter', $results);
			return $saved_files;

		}

		public static function get_expressone_json($company_id = false, $username = false, $password = false) {
			$need_sync = self::check_if_sync_needed('expressone');
			if(!$need_sync) return false;

			//Check API key
			if(!$company_id || !$username || !$password) {
				$company_id = VP_Woo_Pont_Helpers::get_option('expressone_company_id');
				$username = VP_Woo_Pont_Helpers::get_option('expressone_username');
				$password = VP_Woo_Pont_Helpers::get_option('expressone_password');
			}

			//If theres no api key, return error
			if(!$company_id || !$username || !$password) {
				return false;
			}

			//Setup auth
			$body = array(
				'auth' => array(
					'company_id' => $company_id,
					'user_name' => $username,
					'password' => $password
				)
			);

			//Make request for json file
			$request = wp_remote_post('https://webcas.expressone.hu/webservice/eoneshop/get_list/response_format/json', array(
				'body'    => json_encode($body),
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
				),
				'timeout' => 60
			));

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'expressone-import-points');
				return false;
			}

			//Parse response
			$response = wp_remote_retrieve_body( $request );
			$response = json_decode( $response, true );

			//If not successful
			if(!$response['successfull']) {
				VP_Woo_Pont()->log_error_messages($request, 'expressone-import-points');
			}

			//Create a new json
			$results = array('alzabox' => array(), 'packeta' => array(), 'omv' => array());

			//Valod groups, so only sync what we really need
			$enabled_groups = array('omv', 'alzabox', 'packeta');

			//Simplify json, so its smaller to store, faster to load
			foreach ($response['response'] as $place) {
				$name = str_replace('()', '', $place['name']);
				$result = array(
					'id' => $place['code'],
					'lat' => $place['latitude'],
					'lon' => $place['longitude'],
					'name' => $name,
					'zip' => $place['zip_code'],
					'addr' => $place['street'],
					'city' => $place['city'],
					'group' => $place['provider']
				);

				if(in_array($place['provider'], $enabled_groups)) {
					$results[$place['provider']][] = $result;
				}
			}

			//Save stuff
			$saved_files = array();
			foreach ($results as $type => $points) {
				$saved_files['expressone_'.$type] = self::save_json_file('expressone_'.$type, $points);
			}

			return $saved_files;
		}

		public static function get_gls_json() {
			$need_sync = self::check_if_sync_needed('gls');
			if(!$need_sync) return false;

			//Get supported countries
			$enabled_countries = get_option('vp_woo_pont_gls_countries', array('HU'));

			//Create a new json
			$results = array('shop' => array(), 'locker' => array());

			//Get data for each country
			foreach($enabled_countries as $enabled_country) {
				$country_code = strtolower($enabled_country);
				$request = wp_remote_get('https://map.gls-hungary.com/data/deliveryPoints/'.$country_code.'.json');

				//Check for errors
				if( is_wp_error( $request ) ) {
					VP_Woo_Pont()->log_error_messages($request, 'gls-import-points');
					return false;
				}

				//Get body
				$body = wp_remote_retrieve_body( $request );

				//Try to convert into json
				$json = json_decode( $body, true );

				//Check if json exists
				if($json === null) {
					return false;
				}

				//Simplify json, so its smaller to store, faster to load
				foreach ($json['items'] as $place) {

					//For now, only hungarian points
					$result = array(
						'id' => $place['id'],
						'lat' => number_format($place['location'][0], 5, '.', ''),
						'lon' => number_format($place['location'][1], 5, '.', ''),
						'zip' => $place['contact']['postalCode'],
						'addr' => $place['contact']['address'],
						'city' => $place['contact']['city'],
						'country' => strtolower($place['contact']['countryCode']),
					);

					//Skip the ones where the ID doesn't containt a -
					if(strpos($result['id'], '-') === false) {
						continue;
					}

					// Split name at " | " (if it exists)
					if($place['name'] && strpos($place['name'], ' | ') !== false) {
						$name_parts = explode(' | ', $place['name'], 2);
						$result['name'] = trim($name_parts[0]);
						$result['comment'] = trim($name_parts[1]);
					} else {
						$result['name'] = $place['name'];
					}

					// Add comment about the place (if it exists)
					if(isset($place['description']) && !empty($place['description'])) {
						$result['comment'] = isset($result['comment']) ? $result['comment'].' '.$place['description'] : $place['description'];
					}

					//Open hours
					if(isset($place['hours']) && count($place['hours']) > 0) {
						$result['hours'] = array();
						foreach ($place['hours'] as $hour) {
							$day = $hour[0];
							$start = $hour[1];
							$end = $hour[2];
							$result['hours'][$day] = $start.' - '.$end;
						}

						//Check if its 0-24 all week(so the json is smaller)
						$open_days_count = array_unique($result['hours']);
						if(count($result['hours']) == 7 && count($open_days_count) == 1) {
							$times = $result['hours'][1];
							$result['hours'] = $times;
						}
					}

					//Group parcel lockers and shops
					if($place['type'] == 'parcel-locker') {
						$results['locker'][] = $result;
					} else {
						$results['shop'][] = $result;
					}

				}
				
			}

			//Save stuff
			$saved_files = array();
			foreach ($results as $type => $points) {
				$saved_files['gls_'.$type] = self::save_json_file('gls_'.$type, $points);
			}

			return $saved_files;
		}

		public static function get_dpd_json($username = false, $password = false) {
			$need_sync = self::check_if_sync_needed('dpd');
			if(!$need_sync) return false;

			//Get enabled countries
			$enabled_countries = get_option('vp_woo_pont_dpd_countries', array('HU'));
			$country_list = implode(',', $enabled_countries);

			//Get the data
			$request = wp_remote_get('https://points-api.kvikk.hu/points?search=dpd&country='.$country_list, array(
				'timeout' => 60
			));

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'dpd-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Create a new json
			$results = array('alzabox' => array(), 'parcelshop' => array());

			//Simplify json, so its smaller to store, faster to load
			foreach ($json['data'] as $place) {
				if(!in_array($place['country'], $enabled_countries)) {
					continue;
				}

				//Create results
				unset($place['courier']);

				if($place['type'] == 'alzabox') {
					$results['alzabox'][] = $place;
				} else {
					$results['parcelshop'][] = $place;
				}
				
			}

			//Save stuff
			$saved_files = array();
			foreach ($results as $type => $points) {
				$saved_files['dpd_'.$type] = self::save_json_file('dpd_'.$type, $points);
			}

			return $saved_files;
		}

		public static function get_sameday_json() {
			$need_sync = self::check_if_sync_needed('sameday');
			if(!$need_sync) return false;

			//Get zip code ids - sameday stores the poscodes in a different format, so we need to convert that later into actual postcodes
			$request = wp_remote_get('https://www.vaterafutar.hu/sameday/ajax?action=getlockers');

			//Check for errors
			if( is_wp_error( $request ) ) {
				VP_Woo_Pont()->log_error_messages($request, 'sameday-import-points');
				return false;
			}

			//Get body
			$body = wp_remote_retrieve_body( $request );

			//Try to convert into json
			$json = json_decode( $body, true );

			//Check if json exists
			if($json === null) {
				return false;
			}

			//Create a new json
			$results = array();

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $easybox) {

				//Only hungarian points
				//if($easybox['countryId'] != 237) continue;

				$result = array(
					'id' => $easybox['id'],
					'lat' => number_format($easybox['lat'], 5, '.', ''),
					'lon' => number_format($easybox['long'], 5, '.', ''),
					'name' => $easybox['name'],
					'zip' => $easybox['postalcode'],
					'addr' => $easybox['address'],
					'city' => $easybox['city'],
					'cod' => ($easybox['supportedPayment'] == 1)
				);

				//Open hours
				if(isset($easybox['schedule'])) {
					$schedule = json_decode($easybox['schedule'], true);
					if(count($schedule) > 0) {
						$result['hours'] = array();
						foreach ($schedule as $day) {
							$result['hours'][$day['day']] = $day['openingHour'].' - '.$day['closingHour'];
						}
	
						//Check if its 0-24 all week(so the json is smaller)
						$open_days_count = array_unique($result['hours']);
						if(count($result['hours']) == 7 && count($open_days_count) == 1) {
							$times = $result['hours'][1];
							$result['hours'] = $times;
						}	
					}
				}

				$results[] = $result;
			}

			//Save stuff only if results are not empty
			$saved_files = array();
			if(!empty($results)) {
				$saved_files['sameday'] = self::save_json_file('sameday', $results);
			}
			return $saved_files;
		}

		public static function save_json_file($provider, $json) {

			//Allow plugins to customize
			$json = apply_filters('vp_woo_pont_db_import_'.$provider, $json);
			$count = count($json);

			//Create smaller json
			$smaller_json = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			//Get filename and download folder
			$paths = VP_Woo_Pont_Helpers::get_download_folder($provider);
			$filename = $paths['name'];

			//Remove existing file
			$existing_filename = get_option('_vp_woo_pont_file_'.$provider);
			if($existing_filename) {
				$existing_file = $paths['dir'].$existing_filename;
				if(file_exists($existing_file)){
					unlink($existing_file);
				}
			}

			//Save the file
			file_put_contents($paths['path'], $smaller_json);

			//Store current file name in db
			update_option('_vp_woo_pont_file_'.$provider, $filename);
			update_option('_vp_woo_pont_file_'.$provider.'_count', $count);

			return $filename;
		}

		public static function import_manually() {
			check_ajax_referer( 'vp-woo-pont-settings', 'nonce' );

			//Get provider id
			$provider = sanitize_text_field($_POST['provider']);
			$pont_type = sanitize_text_field($_POST['provider']);
			$pont_type = explode('_', $pont_type);
			$carrier = $pont_type[0];

			//Skip sync check
			self::$skip_check = true;

			//Try to run the import
			$func = apply_filters('vp_woo_pont_import_'.$carrier.'_manually', array(__CLASS__, 'get_'.$carrier.'_json'));
			$import = call_user_func($func);

			//Return response
			if($import) {
				$download_folders = VP_Woo_Pont_Helpers::get_download_folder();
				$updated_file = get_option('_vp_woo_pont_file_'.$carrier);
				$count = get_option('_vp_woo_pont_file_'.$provider.'_count');
				wp_send_json_success(array(
					'url' => $download_folders['url'],
					'message' => __('Import run successfully.', 'vp-woo-pont'),
					'files' => $import,
					'qty' => $count
				));
			} else {
				wp_send_json_error(array(
					'message' => __('Unable to run the import tool for some reason.', 'vp-woo-pont')
				));
			}

		}

		public static function check_if_sync_needed($provider) {
			if(self::$skip_check) {
				return true;
			}

			//Get saved value
			$saved_file = get_option('_vp_woo_pont_file_'.$provider);

			//Get enabled providers
			$enabled_providers = get_option('vp_woo_pont_enabled_providers');

			//Check enabled providers
			$enabled = false;
			foreach ($enabled_providers as $enabled_provider) {
				if($provider == 'postapont' || $provider == 'gls' || $provider == 'packeta') {
					if (strpos($enabled_provider, $provider) !== false) {
						$enabled = true;
					}
				} else {
					if($provider == $enabled_provider) {
						$enabled = true;
					}
				}
			}

			if($saved_file && !$enabled) {
				return false;
			} else {
				return true;
			}

		}

	}

 VP_Woo_Pont_Import_Database::init();

endif;
