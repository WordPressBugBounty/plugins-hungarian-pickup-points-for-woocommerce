<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="vp-woo-pont-walkthrough about__container">
	<div class="vp-woo-pont-walkthrough-header">
		<h1>Beállítás varázsló</h1>
		<h2>Csomagpontos szállítási módok</h2>
		<p>Ez a beállítás varázsló segít beállítani a most telepített csomagpontos bővítményt.</p>
	</div>
	<form class="vp-woo-pont-walkthrough-form">
		<div class="vp-woo-pont-walkthrough-steps">
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-providers">
				<h3>Az első kérdés, hogy melyik szolgáltatókat fogod használni?</h3>
				<?php $providers = VP_Woo_Pont_Helpers::get_supported_providers(); ?>
                <?php 
                // Group providers by carrier
                $grouped_providers = [];
                foreach ($providers as $provider_id => $label) {
                    $carrier = explode('_', $provider_id)[0];
                    if (!isset($grouped_providers[$carrier])) {
                        $grouped_providers[$carrier] = [];
                    }
                    $grouped_providers[$carrier][$provider_id] = $label;
                }
                ?>
                <ul>
                    <?php foreach ($grouped_providers as $carrier => $carrier_providers): ?>
						<?php if($carrier == 'custom') continue; ?>
                        <li>
                            <h4><?php echo VP_Woo_Pont_Helpers::get_provider_name($carrier, true); ?></h4>
                            <ul>
                                <?php foreach ($carrier_providers as $provider_id => $label): ?>
									<li>
                                        <input type="checkbox" data-carrier="<?php echo esc_attr($carrier); ?>" id="provider-<?php echo esc_attr($provider_id); ?>" name="providers[]" value="<?php echo esc_attr($provider_id); ?>">
                                        <label for="provider-<?php echo esc_attr($provider_id); ?>">
                                            <i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
                                            <strong><?php echo esc_attr($label); ?></strong>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
				<p class="description">Pipáld be a megfelelő opciókat</p>
				<div class="step-dpd">
					<h3>Add meg a DPD Weblabel belépési adataidat</h3>
					<div class="vp-woo-pont-walkthrough-step-field">
						<input type="text" name="dpd_username" placeholder="Weblabel felhasználónév">
						<input type="text" name="dpd_password" placeholder="Weblabel jelszó">
					</div>
					<p class="description">A DPD átvevőpont lista letöltéséhez szükség van a Weblabel-es felhasználónévre és jelszóra.</p>
				</div>
				<div class="step-expressone">
					<h3>Add meg az Express One belépési adataidat</h3>
					<div class="vp-woo-pont-walkthrough-step-field">
						<input type="text" name="expressone_company_id" placeholder="Cég azonosítószám">
						<input type="text" name="expressone_username" placeholder="Felhasználónév">
						<input type="text" name="expressone_password" placeholder="Jelszó">
					</div>
					<p class="description">Az Express One átvevőpont lista letöltéséhez szükség van a Webcas belépési adatokra.</p>
				</div>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-cost">
				<h3>Mennyi legyen a szállítási költség?</h3>
				<?php $providers = VP_Woo_Pont_Helpers::get_supported_providers(); ?>
				<ul>
					<?php foreach ($providers as $provider_id => $label): ?>
					<li class="cost-row-<?php echo esc_attr($provider_id); ?>">
						<label for="provider-cost-<?php echo esc_attr($provider_id); ?>">
							<i class="vp-woo-pont-provider-icon-<?php echo esc_attr($provider_id); ?>"></i>
							<strong><?php echo esc_html($label); ?></strong>
						</label>
						<input type="text" id="provider-cost-<?php echo esc_attr($provider_id); ?>" name="shipping_cost[<?php echo esc_attr($provider_id); ?>]" placeholder="1000">
						<small><?php echo esc_html(get_woocommerce_currency_symbol()); ?></small>
					</li>
					<?php endforeach; ?>
				</ul>
				<p class="description">A WooCommerce-ben a szállítási költséget <strong>nettó</strong> árként tudod megadni. Természetesen van lehetőség egyedi árakat beállítani különböző feltételek szerint(kosár összeg, súly térfogat stb...). Ezt később a beállításokban megteheted.</p>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-design">
				<h3>Milyen néven jelenjen meg ez a szállítási mód?</h3>
				<div class="vp-woo-pont-walkthrough-step-field">
					<input type="text" name="method_name" placeholder="Személyes átvétel">
				</div>
				<p class="description">Ha csak egy szolgáltatót használsz, nyugodtan lehet annak a neve, például <strong>Foxpost csomagautomata</strong>. Ha több szolgáltatód is van, akkor például <strong>Személyes átvétel</strong> egy jó elnevezés.</p>

				<h3 class="vp-woo-pont-walkthrough-step-design-color">Melyik a weboldalad elsődleges színe?</h3>
				<div class="vp-woo-pont-walkthrough-step-field">
					<input type="text" name="primary_color" class="color-picker vp-woo-pont-color-picker" placeholder="#2371B1" value="#2371B1">
				</div>
				<p class="description">A csomagpont választó felületen ez a szín fog dominálni(gombok, címek színe). További szín és betűméret beállításokat a <strong>Megjelenés / Testreszabás / WooCommerce / Csomagpont</strong> menüben találsz.</p>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-zone">
				<h3>Melyik szállítási zónában legyen elérhető ez a szállítási mód?</h3>
				<?php $delivery_zones = WC_Shipping_Zones::get_zones(); ?>
				<ul>
					<?php if(empty($delivery_zones)): ?>
						<?php $default_zone = new WC_Shipping_Zone(0); ?>
						<?php $default_zone_data = $default_zone->get_data(); ?>
						<li>
							<input type="checkbox" id="zone-<?php echo esc_attr($default_zone_data['id']); ?>" name="zones[]" value="<?php echo esc_attr($default_zone_data['id']); ?>">
							<label for="zone-<?php echo esc_attr($default_zone_data['id']); ?>">
								<strong><?php echo esc_html($default_zone_data['zone_name']); ?></strong>
							</label>
						</li>
					<?php endif; ?>
					<?php foreach ((array) $delivery_zones as $key => $the_zone): ?>
					<li>
						<input type="checkbox" id="zone-<?php echo esc_attr($the_zone['id']); ?>" name="zones[]" value="<?php echo esc_attr($the_zone['id']); ?>">
						<label for="zone-<?php echo esc_attr($the_zone['id']); ?>">
							<strong><?php echo esc_html($the_zone['zone_name']); ?></strong>
							<small><?php echo esc_html($the_zone['formatted_zone_location']); ?></small>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php if(!empty($delivery_zones)): ?>
				<p class="description">Pipáld be a megfelelő zónákat</p>
				<?php endif; ?>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-pro">
				<h3>Rendelkezel a PRO verziós licensz kulccsal?</h3>
				<div class="vp-woo-pont-walkthrough-step-field">
					<input type="text" name="pro_key" placeholder="Licensz kulcs" value="<?php echo esc_attr(VP_Woo_Pont_Pro::get_license_key()); ?>">
				</div>
				<p>Miért érdemes a PRO verziót használni?</p>
				<ul>
					<li>Címkenyomtatás funkció csoportos generálással, letöltéssel</li>
					<li>Követési szám megjelenítése a vásárlónak(e-mailben, fiókom oldalon)</li>
					<li>Címkenyomtatás automatizálása rendelési státuszok és egyéb feltételek szerint</li>
					<li>Követési szám megjelenítése a Számlázz.hu és Billingo számlákon</li>
					<li>Feltételek szerint elrejthető szállítási mód</li>
					<li>Prémium e-mailes és telefonos támogatás</li>
					<li>Webshippy kompatibilitás és még sok más</li>
				</ul>
				<div class="vp-woo-pont-walkthrough-step-pro-cta">
					<a href="https://visztpeter.me/woocommerce-csomagpont-integracio/" target="_blank"><span class="dashicons dashicons-cart"></span> <span><?php esc_html_e( 'Purchase PRO version', 'vp-woo-pont' ); ?></span></a>
					<span>
						<small><?php esc_html_e( 'from', 'vp-woo-pont' ); ?></small>
						<strong><?php esc_html_e( '35 EUR / year', 'vp-woo-pont' ); ?></strong>
					</span>
				</div>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-kvikk">
				<h3>Kvikk</h3>
				<p>Ha csomagszállításos megoldást keresel, nézd meg az új szolgáltatásom:</p>
				<ul>
					<li>Kedvezményes árakon(<strong>nettó 750 Ft-tól</strong>) szerződhetsz velünk a népszerű futárszolgálatokkal kiscsomag szállításra</li>
					<li>Egyszerű, azonnali regisztráció vállalkozások és cégek számára</li>
					<li>Teljesen publikus súly alapú --árazás, nincs mennyiség limit és rejtett felárak</li>
					<li>Nem kell külön szerződéskötés minden egyes futárszolgálattal</li>
					<li>Egységes és átlátható elszámolás, nem kell több helyre fizetned</li>
				</ul>
				<div class="vp-woo-pont-walkthrough-step-kvikk-cta">
					<a href="https://kvikk.hu/" target="_blank"><span><?php esc_html_e( 'Bővebb infó', 'vp-woo-pont' ); ?></span> <span class="dashicons dashicons-arrow-right-alt"></span></a>
				</div>
			</div>
			<div class="vp-woo-pont-walkthrough-step vp-woo-pont-walkthrough-step-success">
				<h3>A csomagpont választó használatra kész!</h3>
				<p>Sikerült minden fontos dolgot beállítani, most már használatra kész a csomagpontos szállítási mód!</p>
				<p>A beállítások között még sok fontos dolgot találsz, a címkenyomtatás beállításait például.</p>
				<nav>
					<a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' )); ?>" class="button button-primary button-hero">Tovább a beállításokhoz</a>
					<a href="<?php echo esc_url(get_permalink( wc_get_page_id( 'shop' ) )); ?>" class="button button-hero">Kipróbálom a pénztár oldalon</a>
				</nav>
			</div>
		</div>
		<?php wp_nonce_field( 'vp-woo-pont-walkthrough', 'security' ); ?>
		<input type="hidden" name="action" value="vp_woo_pont_walkthrough_finish">
	</form>
	<nav class="vp-woo-pont-walkthrough-nav">
		<a href="#" class="button button-hero vp-woo-pont-walkthrough-nav-previous">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<span>Vissza</span>
		</a>
		<a href="#" class="button button-primary button-hero vp-woo-pont-walkthrough-nav-next">
			<span>Tovább</span>
			<span class="dashicons dashicons-arrow-right-alt2"></span>
		</a>
		<a href="#" data-url="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=shipping&section=vp_pont' )); ?>" data-nonce="<?php echo wp_create_nonce( 'vp-woo-pont-cancel-setup-wizard' )?>" class="vp-woo-pont-walkthrough-nav-cancel">Nem érdekel a varázsló</a>
	</nav>
</div>
