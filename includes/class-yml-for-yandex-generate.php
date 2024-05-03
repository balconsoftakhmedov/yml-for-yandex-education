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
			$this->setupSets( 'learning_way' );
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

	public function getAllTerms( $taxonomy ) {

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			return $terms;
		}

		return [];
	}

	private function setupSets( $taxonomy ) {

		$taxonomies = $this->getAllTerms( $taxonomy );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $term ) {
				$post_id   = $term->term_id;
				$post_name = $term->name;
				$post_url  = get_term_link( $term );
				$set       = $this->sets->addChild( 'set' );
				$set->addAttribute( 'id', $post_id );
				$set->addChild( 'name', $post_name );
				$set->addChild( 'url', $post_url );
			}
		}
		wp_reset_postdata();
	}

	public function addWebinarOffers( $webinar_posts ) {

		foreach ( $webinar_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
			$category_id = $this->addLearningWays( $post->ID );
			$offer->addChild( 'categoryId', $category_id );
			$desc = get_field( 'meetup_more', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params = [
				'meetup_date'        => 'Дата проведения',
				'time' => 'Время',
				'meetup_speakers' => 'Спикеры',
				'meetup_address' => 'Где будет проходить'
			];
			foreach ( $params as $key => $value ) {
				if ( 'meetup_date' == $key ) {
					$v = get_field( $key, $post->ID, false );
					$v = $this->get_desc( $v );
					$timestamp = strtotime( $v );
					$wp_date = date_i18n( get_option( 'date_format' ), $timestamp );
					$this->addParam( $offer, $value, $this->get_desc( $wp_date ) );
				} else {
					$v = get_field( $key, $post->ID, false );
					$this->addParam( $offer, $value, $this->get_desc( $v ) );
				}
			}
		}
	}

	public function addLearningOffers( $learning_posts ) {
		foreach ( $learning_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
			$category_id = $this->addLearningWays( $post->ID );
			$offer->addChild( 'categoryId', $category_id );
			$setIds = $this->addLearningWays( $post->ID, 'learning_way', 'all' );
			$offer->addChild( 'set-ids', $setIds );
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
	public function addSeminarOffers($seminar_posts){
				foreach ( $webinar_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
			$category_id = $this->addLearningWays( $post->ID );
			$offer->addChild( 'categoryId', $category_id );
			$desc = get_field( 'meetup_more', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params = [
				'meetup_date'        => 'Дата проведения',
				'time' => 'Время',
				'meetup_speakers' => 'Спикеры',
				'meetup_address' => 'Где будет проходить'
			];
			foreach ( $params as $key => $value ) {
				if ( 'meetup_date' == $key ) {
					$v = get_field( $key, $post->ID, false );
					$v = $this->get_desc( $v );
					$timestamp = strtotime( $v );
					$wp_date = date_i18n( get_option( 'date_format' ), $timestamp );
					$this->addParam( $offer, $value, $this->get_desc( $wp_date ) );
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

	private function addLearningWays( $post_id, $taxonomy = 'learning_way', $key = 0 ) {
		$terms     = get_the_terms( $post_id, $taxonomy );
		$terms_all = [];
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $key == 0 ) {
					return $term->term_id;
				} else {
					$terms_all[] = $term->term_id;
				}
			}

			return implode( ',', $terms_all );
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
		$desc_stripped = str_replace( '&nbsp;', '', $desc_stripped );
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