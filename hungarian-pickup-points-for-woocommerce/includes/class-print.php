<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//Load PDF API
use iio\libmergepdf\Merger;
use iio\libmergepdf\Driver\TcpdiDriver;
use setasign\Fpdi\PdfParser\StreamReader;

if ( ! class_exists( 'VP_Woo_Pont_Print', false ) ) :

	class VP_Woo_Pont_Print {

		//Constructor
		public static function init() {

			// Not using Jetpack\Constants here as it can run before 'plugin_loaded' is done.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX
				|| defined( 'DOING_CRON' ) && DOING_CRON
				|| ! is_admin() ) {
				return;
			}

			//Load template based on get parameter
			add_action( 'admin_init', array( __CLASS__, 'load_pdf_file') );

		}
		
		public static function fit_to_a6($pdf) {
			require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
			$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => 'A6', 'orientation' => 'P'));
			$stream = StreamReader::createByString($pdf);
			$mpdf->AddPage();
			$mpdf->setSourceFile($stream);
			$label = $mpdf->ImportPage(1);
			$mpdf->UseTemplate($label, 0, 0);
			return $mpdf->Output('file.pdf', 'S');
		}

		public static function crop_to_a6($pdf) {
			require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
			$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => 'A6', 'orientation' => 'P'));
			$stream = StreamReader::createByString($pdf);
			$mpdf->AddPage();
			$mpdf->setSourceFile($stream);
			$label = $mpdf->ImportPage(1);
			
			// Get the original template size
			$templateSize = $mpdf->getTemplateSize($label);
			
			// Scale by 1.1 (10% larger)
			$scale = 1.2;
			$newWidth = $templateSize['width'] * $scale;
			$newHeight = $templateSize['height'] * $scale;
			
			$mpdf->UseTemplate($label, 2, 2, $newWidth, $newHeight);
			
			return $mpdf->Output('file.pdf', 'S');
		}

		public static function crop_to_a6_two($pdf) {
			require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
			$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => 'A4', 'orientation' => 'P'));
			$stream = StreamReader::createByString($pdf);
			$mpdf->AddPage();
			$mpdf->setSourceFile($stream);
			$mpdf->Rotate(-90);
			$label = $mpdf->ImportPage(1);
			$mpdf->UseTemplate($label, -4, -74);
			return $mpdf->Output('file.pdf', 'S');
		}

		public static function rotate_to_a6($pdf, $position = false) {
			require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
			$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => 'A6', 'orientation' => 'P'));
			$mpdf->AddPage();
			$mpdf->setSourceFile($pdf);
			$mpdf->Rotate(-90);
			$label = $mpdf->ImportPage(1);
			if($position) {
				$mpdf->UseTemplate($label, $position[0], $position[1]);
			} else {
				$mpdf->UseTemplate($label, -10, -74);
			}
			return $mpdf->Output('file.pdf', 'S');
		}

		public static function pdf_to_png_pdf($pdf) {
			require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

			try {

				//Load PDF with Imagick at specified DPI
				$dpi = 200;
				$imagick = new \Imagick();
				$imagick->setResolution($dpi, $dpi);
				$imagick->readImageBlob($pdf); // First page only
				$imagick->setImageFormat('png');

				//Get original dimensions in pixels
				$widthPx = $imagick->getImageWidth();
				$heightPx = $imagick->getImageHeight();

				//Convert pixels to millimeters: mm = pixels * 25.4 / DPI
				$widthMM = $widthPx * 25.4 / $dpi;
				$heightMM = $heightPx * 25.4 / $dpi;

				// Save image temporarily
				$tmp_img = tempnam(sys_get_temp_dir(), 'label_') . '.png';
				$imagick->writeImage($tmp_img);
				$imagick->clear();
				$imagick->destroy();

				//Create a new PDF with the same dimensions
				$mpdf = new \Mpdf\Mpdf([
					'mode' => 'c',
					'format' => [$widthMM, $heightMM],
					'orientation' => 'P',
					'img_dpi' => $dpi,
				]);

				//Add the image to the PDF
				$mpdf->AddPage();
				$mpdf->Image($tmp_img, 0, 0, $widthMM, $heightMM, 'png', '', true, false);

				// Clean up
				unlink($tmp_img);

				return $mpdf->Output('file.pdf', 'S');
				
			} catch (\ImagickException $e) {
				return false;
			}

		}

		public static function load_pdf_file($orders = false, $output = 'file') {

			//Check for parameters
			if(!$orders && !isset( $_GET['vp_woo_pont_label_pdf'] )) {
				return;
			}

			//Get submitted parameters
			$order_id = $orders;
			$position = 1;
			if(!$orders) {
				$order_id = sanitize_text_field($_GET['vp_woo_pont_label_pdf']);
				$position = intval($_GET['position']);
			}

			//Handle single and bulk print
			if(strpos($order_id,',') !== false) {

				//Get ids as an array
				$order_ids = explode(',', $order_id);
				$order_ids = array_map('absint', $order_ids);

				//If we need to zip the files, we need to do it here
				if(isset($_GET['format']) && $_GET['format'] == 'zip') {

					//Create an object from the ZipArchive class.
					$zipArchive = new ZipArchive();
					$zip_filename = 'labels.zip';
					if ($zipArchive->open($zip_filename, ZipArchive::CREATE) !== true) {
						die("Cannot open $zip_filename\n");
					}

					//An array of files that we want to add to our zip archive.
					foreach ( $order_ids as $order_id ) {
						$order = wc_get_order($order_id);
						if($order && VP_Woo_Pont()->labels->is_label_generated($order_id)) {
							$pdf_file = VP_Woo_Pont()->labels->generate_download_link($order, true);
							if($pdf_file && file_exists($pdf_file)) {

								//Add file to zip
								$zipArchive->addFile($pdf_file, basename($pdf_file));

							}
						}
					}

					//Finally, close the active archive.
					$zipArchive->close();

					// Set appropriate headers for ZIP file download
					header('Content-Type: application/zip');
					header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
					header('Content-Length: ' . filesize($zip_filename));

					// Output the ZIP file
					readfile($zip_filename);

					// Delete the ZIP file after sending it to the browser
					unlink($zip_filename);

				}

				//Init mPDF
				require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

				//Process selected orders, first by grouping them into providers
				$grouped_by_providers = array();
				foreach ( $order_ids as $order_id ) {
					$order = wc_get_order($order_id);
					if($order && VP_Woo_Pont()->labels->is_label_generated($order_id)) {

						//Get provider id
						$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
						if(!isset($grouped_by_providers[$provider_id])) $grouped_by_providers[$provider_id] = array();

						//And create a new array, which is order ids grouped by providers
						$pdf_file = VP_Woo_Pont()->labels->generate_download_link($order, true);
						if($pdf_file && file_exists($pdf_file)) {
							$grouped_by_providers[$provider_id][] = $pdf_file;
						}
					}
				}

				//Check if we are using the same size for all providers
				$merge_print = true;
				if(count($grouped_by_providers) > 1) {
					foreach($grouped_by_providers as $provider_id => $pdf_files) {
						$sticker_parameters = VP_Woo_Pont_Helpers::get_pdf_label_positions($provider_id);
						if($sticker_parameters['sticker'] != 'A6') {
							$merge_print = false;
							break;
						}
					}
				}

				//If we only have one type of provider, we can use the layout options to skip a label when printing
				$sticker_parameter = false;
				if(apply_filters('vp_woo_pont_print_group_by_providers', $merge_print)) {

					//Get provider type
					$provider = array_key_first($grouped_by_providers);

					//Get sticker positions
					$sticker_parameters = VP_Woo_Pont_Helpers::get_pdf_label_positions($provider);

					//Checks if selected label size supports bulk printing
					if($sticker_parameters['format']) {
						$sticker_parameter = $sticker_parameters;
					}

					//Reset groups
					$merged_grouped_by_providers = array();
					foreach ($grouped_by_providers as $provider_id => $pdf_files) {
						foreach ($pdf_files as $pdf_file) {
							$merged_grouped_by_providers[] = $pdf_file;
						}
					}
					$grouped_by_providers = $merged_grouped_by_providers;

				}
				
				if($sticker_parameter) {

					//Merge onto A4 pages
					require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
					$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => $sticker_parameters['format']));
					$mpdf->AddPage();

					$label_counter = $position;
					$pdf_files = $grouped_by_providers;

					foreach ($pdf_files as $key => $pdf_file) {
						$mpdf->setSourceFile($pdf_file);
						$pagecount = $mpdf->setSourceFile($pdf_file);
				
						for ($i = 1; $i <= $pagecount; $i++) {
							$label_counter++;
							$label = $mpdf->ImportPage($i);
							$mpdf->UseTemplate($label, $sticker_parameter['x'][$label_counter-1], $sticker_parameter['y'][$label_counter-1]);
				
							if ($label_counter == $sticker_parameter['sections'] && $key !== array_key_last($pdf_files)) {
								$label_counter = 0;
								$mpdf->AddPage();
							}
						}
					}
				
					if($output == 'file') {
						header("Content-type:application/pdf");
						$mpdf->Output();
						exit();
					} else {
						return $mpdf->Output('file.pdf', 'S');
					}

				} else {

					//Just merge one by one
					require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
					$merger = new Merger(new TcpdiDriver);

					foreach ($grouped_by_providers as $pdf_files) {
						if(is_array($pdf_files)) {
							foreach($pdf_files as $pdf_file) {
								$merger->addFile($pdf_file);
							}
						} else {
							$merger->addFile($pdf_files);
						}
					}

					//If we need to return a file
					if($output == 'file') {
						header("Content-type:application/pdf");
						echo $merger->merge();
						exit();
					} else {
						return $merger->merge();
					}

				}

			} else {

				//Get order details
				$order = wc_get_order($order_id);
				$position_id = $position-1;
				if($position_id < 0) $position_id = 0;
				if(!$order) return;
				$pdf_file = VP_Woo_Pont()->labels->generate_download_link($order, true);
				if(!$pdf_file) return;

				//Get sticker parameters
				$provider_id = VP_Woo_Pont_Helpers::get_carrier_from_order($order);
				$sticker_parameters = VP_Woo_Pont_Helpers::get_pdf_label_positions($provider_id);

				//If we have sticker parameters, we need to modify the label print layout
				if ($sticker_parameters && $sticker_parameters['layout']) {
					require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
					$mpdf = new \Mpdf\Mpdf(array('mode' => 'c', 'format' => $sticker_parameters['format']));
					$pagecount = $mpdf->setSourceFile($pdf_file);
				
					$label_counter = $position_id;
				
					for ($i = 1; $i <= $pagecount; $i++) {
						if ($label_counter == 0 || $label_counter % $sticker_parameters['sections'] == 0) {
							if ($label_counter != 0) {
								$mpdf->AddPage();
							}
							$label_counter = 0;
						}
				
						$label = $mpdf->ImportPage($i);
						$mpdf->UseTemplate($label, $sticker_parameters['x'][$label_counter], $sticker_parameters['y'][$label_counter]);
						$label_counter++;
					}
				
					if ($output == 'file') {
						header("Content-type:application/pdf");
						$mpdf->Output();
						exit();
					} else {
						return $mpdf->Output('file.pdf', 'S');
					}
				} else {
					if($output == 'file') {
						//Set headers for PDF return
						header("Content-type:application/pdf");

						//Display PDF file, so it can be printed
						readfile($pdf_file);
						exit();
					} else {
						return file_get_contents($pdf_file);
					}
				}

			}
		}

	}

	VP_Woo_Pont_Print::init();

endif;
