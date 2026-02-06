<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'VP_Woo_Pont_Import_Database', false ) ) :

	class VP_Woo_Pont_Import_Database {
		private static $skip_check = false;

		public static function get_pont_types() {
			return apply_filters('vp_woo_pont_import_database_providers', array('foxpost', 'postapont', 'packeta', 'expressone', 'gls', 'dpd', 'sameday', 'kvikk'));
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

				//Download list on activation
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
				$saved_files[] = self::save_json_file(array(
					'courier' => 'postapont',
					'type' => $group_id,
					'country' => 'HU',
					'points' => $points
				));
			}

			//Save to DB
			self::save_json_files('postapont', $saved_files);

			return $saved_files;
		}
		
		public static function get_foxpost_json() {
			$need_sync = self::check_if_sync_needed('foxpost');
			if(!$need_sync) return false;

			$request = wp_remote_get('https://cdn.foxpost.hu/foxplus.json');

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
			$results = array('foxpost' => array());
			$open_days = array('hetfo', 'kedd', 'szerda', 'csutortok', 'pentek', 'szombat', 'vasarnap');

			//ID field name
			$id_field = apply_filters('vp_woo_pont_foxpost_id_field', 'operator_id');

			//Simplify json, so its smaller to store, faster to load
			foreach ($json as $foxpost) {

				// Skip if the name does not start with "FOXPOST "
				if($foxpost['variant'] != 'FOXPOST A-BOX' && $foxpost['variant'] != 'FOXPOST Z-BOX') {
					continue;
				}

				// Remove "FOXPOST " from the beginning of the name
				$name = str_replace('FOXPOST ', '', $foxpost['name']);

				$result = array(
					'id' => $foxpost[$id_field],
					'lat' => number_format($foxpost['geolat'], 5, '.', ''),
					'lon' => number_format($foxpost['geolng'], 5, '.', ''),
					'name' => $name,
					'zip' => $foxpost['zip'],
					'addr' => $foxpost['street'],
					'city' => $foxpost['city'],
					'comment' => wp_strip_all_tags($foxpost['findme'])
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

				$results['foxpost'][] = $result;
			}

			//Save as separate files
			$saved_files = array();
			foreach ($results as $group_id => $points) {
				$saved_files[] = self::save_json_file(array(
					'courier' => 'foxpost',
					'type' => $group_id,
					'country' => 'HU',
					'points' => $points
				));
			}

			//Save to DB
			self::save_json_files('foxpost', $saved_files);

			return $saved_files;

		}

		public static function get_packeta_json($api_key = false) {
			$need_sync = self::check_if_sync_needed('packeta');
			if(!$need_sync) return false;

			//Check API key
			if(!$api_key) {
				$api_key = VP_Woo_Pont_Helpers::get_option('packeta_api_key');
			}

			//Get supported countries
			$enabled_countries = get_option('vp_woo_pont_packeta_countries', array('HU:packeta'));
			$saved_files = array();

			//Get enabled countries where the value doesn'T contain :packeta
			$has_extra_points = false;
			foreach ($enabled_countries as $country_provider) {
				if(strpos($country_provider, ':packeta') === false) {
					$has_extra_points = true;
					break;
				}
			}

			//If we have extra points, but no api key, return error
			if($has_extra_points && !$api_key) {
				return false;
			}

			foreach($enabled_countries as $key => $country_provider) {
				$parts = explode(':', $country_provider);
				$provider_id = $parts[1];
				$country_code = $parts[0];

				//Make request for branches json file
				if($provider_id == 'packeta') {

					//Create a new json
					$results = array('zbox' => array(), 'zpont' => array());
					$request = wp_remote_get('https://points-api.kvikk.hu/points?search=packeta&country='.$country_code, array(
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

						//Create results
						$result = $place;
						$type = $result['type'];
						unset($result['type']);

						//Check if we need to skip Z-Box
						if($type == 'zbox') {
							$results['zbox'][] = $result;
						} elseif($type == 'zpont') {
							$results['zpont'][] = $result;
						}

					}

					//Free up memory immediately after processing
					unset($json);
					unset($body);
					unset($request);

					//Save as separate files
					foreach ($results as $group_id => $points) {
						$saved_files[] = self::save_json_file(array(
							'courier' => 'packeta',
							'type' => $group_id,
							'country' => $country_code,
							'points' => $points
						));
					}

				} else {

					$results = array();
					$request = wp_remote_get('https://www.zasilkovna.cz/api/'.$api_key.'/point.json?ids='.$provider_id, array(
						'timeout' => 100
					));

					//Check for errors
					if( is_wp_error( $request ) ) {
						VP_Woo_Pont()->log_error_messages($request, 'packeta-import-points');
						return false;
					}

					//Get body
					$body = wp_remote_retrieve_body( $request );

					//Try to convert into json
					$json = json_decode( $body );

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
								$results[] = $result;
							}
						}
					}

					//Save as separate files
					$saved_files[] = self::save_json_file(array(
						'courier' => 'packeta',
						'type' => $provider_id,
						'country' => $country_code,
						'points' => $results
					));

				}

			}

			//Save to DB
			self::save_json_files('packeta', $saved_files);

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
			$results = array('alzabox' => array(), 'packeta' => array(), 'omv' => array(), 'exobox' => array());

			//Valid groups, so only sync what we really need
			$enabled_groups = array('omv', 'alzabox', 'packeta', 'exobox');

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
			foreach ($results as $group_id => $points) {
				$saved_files[] = self::save_json_file(array(
					'courier' => 'expressone',
					'type' => $group_id,
					'country' => 'HU',
					'points' => $points
				));
			}

			//Save to DB
			self::save_json_files('expressone', $saved_files);

			return $saved_files;
		}

		public static function get_gls_json() {
			$need_sync = self::check_if_sync_needed('gls');
			if(!$need_sync) return false;

			//Get supported countries
			$enabled_countries = get_option('vp_woo_pont_gls_countries', array('HU'));
			$saved_files = array();

			//Get data for each country
			foreach($enabled_countries as $enabled_country) {

				//Create a new json
				$results = array('shop' => array(), 'locker' => array());

				//Get the data
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
						'features' => []
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

						//Remove the description string from the name if it exists there
						$result['name'] = str_replace($place['description'], '', $result['name']);
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

					//Check if its an express locker
					if($place['features'] && in_array('milkrun', $place['features'])) {
						$result['features'][] = 'milkrun';
					}

					//Group parcel lockers and shops
					if($place['type'] == 'parcel-locker') {
						$results['locker'][] = $result;
					} else {
						$results['shop'][] = $result;
					}

				}

				//Free up memory immediately after processing
				unset($json);
				unset($body);
				unset($request);

				//Save stuff
				foreach ($results as $type => $points) {
					$saved_files[] = self::save_json_file(array(
						'courier' => 'gls',
						'type' => $type,
						'country' => $country_code,
						'points' => $points
					));
				}
				
			}

			//Save to DB
			self::save_json_files('gls', $saved_files);

			return $saved_files;
		}

		public static function get_dpd_json($username = false, $password = false) {
			$need_sync = self::check_if_sync_needed('dpd');
			if(!$need_sync) return false;

			//Get enabled countries
			$enabled_countries = get_option('vp_woo_pont_dpd_countries', array('HU'));
			$saved_files = array();

			//Loop through countries
			foreach ($enabled_countries as $enabled_country) {
				$country_code = strtolower($enabled_country);

				//Get the data
				$request = wp_remote_get('https://points-api.kvikk.hu/points?search=dpd&country='.$enabled_country, array(
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

					//Create results
					unset($place['courier']);

					if($place['type'] == 'alzabox') {
						$results['alzabox'][] = $place;
					} else {
						$results['parcelshop'][] = $place;
					}
					
				}

				//Save stuff
				foreach ($results as $group_id => $points) {
					if(empty($points)) {
						continue;
					}

					$saved_files[] = self::save_json_file(array(
						'courier' => 'dpd',
						'type' => $group_id,
						'country' => $enabled_country,
						'points' => $points
					));
				}

			}

			//Save to DB
			self::save_json_files('dpd', $saved_files);

			return $saved_files;
		}

		public static function get_sameday_json($username = false, $password = false) {
			$need_sync = self::check_if_sync_needed('sameday');
			if(!$need_sync) return false;

			//Get enabled countries
			$enabled_countries = get_option('vp_woo_pont_sameday_countries', array('HU'));
			$saved_files = array();

			//Loop through countries
			foreach ($enabled_countries as $enabled_country) {
				$country_code = strtolower($enabled_country);

				//Get the data
				$request = wp_remote_get('https://points-api.kvikk.hu/points?search=sameday&country='.$enabled_country, array(
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
				$results = array('easybox' => array(), 'pick-pack-pont' => array());

				//Simplify json, so its smaller to store, faster to load
				foreach ($json['data'] as $place) {

					//Create results
					unset($place['courier']);

					if($place['type'] == 'easybox') {
						$results['easybox'][] = $place;
					} else {
						$results['pick-pack-pont'][] = $place;
					}
					
				}

				//Save stuff
				foreach ($results as $type => $points) {
					if(empty($points)) {
						continue;
					}

					$saved_files[] = self::save_json_file(array(
						'courier' => 'sameday',
						'type' => $type,
						'country' => $country_code,
						'points' => $points
					));
				}

			}

			//Save to DB
			self::save_json_files('sameday', $saved_files);

			return $saved_files;
		}

		public static function save_json_file($attrs) {

			//Allow plugins to customize
			$json = apply_filters('vp_woo_pont_db_import_'.$attrs['courier'], $attrs['points']);
			$count = count($json);

			//Create smaller json
			$smaller_json = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			//Get filename and download folder
			$paths = VP_Woo_Pont_Helpers::get_download_folder(join('_', array($attrs['courier'], strtolower($attrs['country']), $attrs['type'])));
			$filename = $paths['name'];

			//Save the file
			file_put_contents($paths['path'], $smaller_json);

			//Return saved file info
			return array(
				'country' => strtoupper($attrs['country']),
				'type' => $attrs['type'],
				'file' => $filename,
				'count' => $count
			);
		}

		public static function save_json_files($provider, $files) {

			$grouped = array();
			foreach ($files as $file) {
				if(!isset($grouped[$file['type']])) {
					$grouped[$file['type']] = array();
				}
				$grouped[$file['type']][] = $file;
			}

			foreach ($grouped as $type => $file) {
				update_option('_vp_woo_pont_db_'.$provider.'_'.$type, $file);
			}

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
				wp_send_json_success(array(
					'courier' => $carrier,
					'url' => $download_folders['url'],
					'message' => __('Import run successfully.', 'vp-woo-pont'),
					'files' => $import,
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
			$enabled_providers = get_option('vp_woo_pont_enabled_providers', array());

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
