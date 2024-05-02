<?php

class YMLCatalogGenerator {
	private $xml;
	private $shop;
	private $currencies;
	private $sets;
	private $offers;

	public function __construct() {
		try {

			$this->xml = new SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><yml_catalog/>' );
			$this->xml->addAttribute( 'date', date( 'Y-m-d H:i' ) );
			$this->shop = $this->xml->addChild( 'shop' );
			$this->setupShopInfo();
			$this->currencies = $this->shop->addChild( 'currencies' );
			$this->setupCurrencies();
			$this->sets = $this->shop->addChild( 'sets' );
			$this->setupSets();
			$this->offers = $this->shop->addChild( 'offers' );
		} catch ( Exception $e ) {

			echo 'Error: ' . $e->getMessage();
		}
	}

	private function setupShopInfo() {

		$site_name_def    = get_bloginfo( 'name' );
		$admin_email  = get_option( 'admin_email' );
		$site_url     = get_site_url();
		$site_company = 'АНО ДПО «Академия развития инновационных технологий»';
		$default_picture = $site_url . '/wp-content/plugins/yml-for-yandex-education/images/screenshot.png';

		$site_description   = get_bloginfo( 'description' );
		$custom_description = '';

		$wpseo_settings     = get_option( 'wpseo_titles' );
		if ( $wpseo_settings ) {
			$site_description = $wpseo_settings['metadesc-home-wpseo'] ?? '';
			$site_company     = $wpseo_settings['company_name'] ?? '';
			$custom_picture   = $wpseo_settings['open_graph_frontpage_image'] ?? '';
			$site_name        = $wpseo_settings['open_graph_frontpage_title'] ?? '';
		}

		$site_name = ! empty( $site_name ) ? $site_name : $site_name_def;
		$description        = ! empty( $custom_description ) ? $custom_description : $site_description;
		$picture         = ! empty( $custom_picture ) ? $custom_picture : $default_picture;

		// Add shop information to XML
		$this->shop->addChild( 'name', $site_name );
		$this->shop->addChild( 'company', $site_company );
		$this->shop->addChild( 'url', $site_url );
		$this->shop->addChild( 'email', $admin_email );
		$this->shop->addChild( 'picture', $picture );
		$this->shop->addChild( 'description', $description );
	}


	private function setupCurrencies() {
		$currency = $this->currencies->addChild( 'currency' );
		$currency->addAttribute( 'id', 'RUR' );
		$currency->addAttribute( 'rate', '1' );
	}

	private function setupSets() {
		$set1 = $this->sets->addChild( 'set' );
		$set1->addAttribute( 'id', 's1' );
		$set1->addChild( 'name', 'Курсы' );
		$set1->addChild( 'url', 'https://courses.sample.s3.yandex.net/list/Courses' );
		$set2 = $this->sets->addChild( 'set' );
		$set2->addAttribute( 'id', 's2' );
		$set2->addChild( 'name', 'Вебинары' );
		$set2->addChild( 'url', 'https://courses.sample.s3.yandex.net/list/Webinars' );
		$set3 = $this->sets->addChild( 'set' );
		$set3->addAttribute( 'id', 's3' );
		$set3->addChild( 'name', 'Семинары' );
		$set3->addChild( 'url', 'https://courses.sample.s3.yandex.net/list/Seminars' );
	}

	public function addLearningOffers( $learning_posts ) {
		foreach ( $learning_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
		}
	}

	public function saveXMLFile( $filename ) {
		$filepath = ABSPATH . $filename;
		if ( $this->xml instanceof SimpleXMLElement ) {
			$this->xml->asXML( $filename );
		} else {
			throw new Exception( 'XML object is not initialized' );
		}
	}
}




