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
			/$this->setupSets();
			$this->offers = $this->shop->addChild( 'offers' );
		} catch ( Exception $e ) {

			echo 'Error: ' . $e->getMessage();
		}
	}

	private function setupShopInfo() {

		$site_name_def      = get_bloginfo( 'name' );
		$admin_email        = get_option( 'admin_email' );
		$site_url           = get_site_url();
		$site_company       = 'АНО ДПО «Академия развития инновационных технологий»';
		$default_picture    = $site_url . '/wp-content/plugins/yml-for-yandex-education/images/screenshot.png';
		$site_description   = get_bloginfo( 'description' );
		$custom_description = '';
		$wpseo_settings     = get_option( 'wpseo_titles' );
		if ( $wpseo_settings ) {
			$site_description = $wpseo_settings['metadesc-home-wpseo'] ?? '';
			$site_company     = $wpseo_settings['company_name'] ?? '';
			$custom_picture   = $wpseo_settings['open_graph_frontpage_image'] ?? '';
			$site_name        = $wpseo_settings['open_graph_frontpage_title'] ?? '';
		}
		$site_name   = ! empty( $site_name ) ? $site_name : $site_name_def;
		$description = ! empty( $custom_description ) ? $custom_description : $site_description;
		$picture     = ! empty( $custom_picture ) ? $custom_picture : $default_picture;
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

		$query = new WP_Query( array(
			'post_type' => 'learning',
			'tax_query' => array(
				array(
					'taxonomy' => 'learning_way',
					'field'    => 'slug'
				),
			),
			'paged'     => 2,
		) );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id   = get_the_ID();
				$post_name = get_the_title();
				$post_url  = get_permalink();
				$set = $this->sets->addChild( 'set' );
				$set->addAttribute( 'id', $post_id );
				$set->addChild( 'name', $post_name );
				$set->addChild( 'url', $post_url );
			}
		}

		wp_reset_postdata();
	}


	public function addLearningOffers( $learning_posts ) {
		foreach ( $learning_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
			$category_id = $this->addLearningWays( $post->ID );
			$offer->addChild( 'categoryId', $category_id );
			$desc = get_field( 'descr', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params = [
				'hours'        => 'Время обучения',
				'format'       => 'Форма обучения',
				'documents'    => 'Получаемые документы',
				'requirements' => 'Условия поступления',
				'for_who'      => 'Для кого это обучение?'
			];
			foreach ( $params as $key => $value ) {
				if ( 'hours' == $key ) {
					$v = get_field( $key, $post->ID, false );
					$this->addParam( $offer, $value, $this->get_desc( $v ), 'час' );
				} else {
					$v = get_field( $key, $post->ID, false );
					$this->addParam( $offer, $value, $this->get_desc( $v ) );
				}
			}
		}
	}

	private function addParam( $parent, $name, $value, $unit = '' ) {
		$param = $parent->addChild( 'param', $value );
		$param->addAttribute( 'name', $name );
		if ( $unit != '' ) {
			$param->addAttribute( 'unit', $unit );
		}
	}

	private function addLearningWays( $post_id ) {
		$terms = get_the_terms( $post_id, 'learning_way' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				return $term->term_id;
			}
		}
	}

	private function get_post_category_id( $post_id ) {
		$categories = get_the_category( $post_id );
		if ( ! empty( $categories ) ) {
			return $categories[0]->term_id;
		}

		return '';
	}

	public function get_desc( $desc ) {

		$desc_stripped = strip_tags( $desc );
		$desc_stripped = wp_trim_words( $desc_stripped, 40, '...' );

		return $desc_stripped;
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