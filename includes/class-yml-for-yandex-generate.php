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
		$site_name   = 'АРИТ проф. образование';
		$site_name   = mb_substr( $site_name, 0, 25 );
		// Add shop information to XML
		$this->shop->addChild( 'name', htmlspecialchars( $site_name, ENT_QUOTES, 'UTF-8' ) );
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

	public function getAllTerms( $taxonomy, $post_type ) {

		$posts = get_posts(array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ));

        $term_ids = [];
        foreach ($posts as $post) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $term_ids[$term->term_id] = $term;
                }
            }
        }

		return array_values($term_ids);;
	}

	private function setupSets( $taxonomy ) {

		$taxonomies = $this->getAllTerms( $taxonomy, 'learning' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $term ) {
				$post_id   = $term->term_id;
				$post_name = $term->name;
				$post_url  = get_term_link( $term );
				$set       = $this->sets->addChild( 'set' );
				$set->addAttribute( 'id', 's' . $post_id );
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


			$category_id = 1000;

			$offer->addChild( 'categoryId', $category_id );
			$offer->addChild( 'currencyId', 'RUR' );
			$offer->addChild( 'price', 0 );
			$course_time = ( get_field( 'hours', $post->ID, false ) ) ? get_field( 'hours', $post->ID, false ) : 6;
			$this->generate_plan($offer, $course_time );


			$desc = get_field( 'meetup_more', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params     = [
				'meetup_date'     => 'Дата проведения',
				'time'            => 'Время',
				'meetup_speakers' => 'Спикеры',
				'meetup_address'  => 'Где будет проходить',
				'hours'        => 'Продолжительность',
			];
			$meetup_pic = get_field( 'meetup_pic', $post->ID, false );
			$offer->addChild( 'picture', $this->get_image( $meetup_pic ) );
			foreach ( $params as $key => $value ) {
				if ( 'meetup_date' == $key ) {
					$v         = get_field( $key, $post->ID, false );
					$v         = $this->get_desc( $v );
					$timestamp = strtotime( $v );
					$wp_date   = date_i18n( get_option( 'date_format' ), $timestamp );
					$this->addParam( $offer, $value, $this->get_desc( $wp_date ) );
				} elseif ( 'hours' == $key ) {
					$v = ( get_field( $key, $post->ID, false ) ) ? get_field( $key, $post->ID, false ) : 6;
					$this->addParam( $offer, $value, $this->get_desc( $v ), 'час' );
				}else {
					$k = get_field( $key, $post->ID, false );
					$v = ($k)? $k: 'Нету данных';
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
			$category_id = 1000;

			$offer->addChild( 'categoryId', $category_id );
			$setIds = $this->addLearningWays( $post->ID, 'learning_way', 'all' );

			if (!empty($setIds)) $offer->addChild( 'set-ids', 's' . $setIds );
			$offer->addChild( 'currencyId', 'RUR' );
			$offer->addChild( 'price', 0 );
			$course_time = ( get_field( 'hours', $post->ID, false ) ) ? get_field( 'hours', $post->ID, false ) : 9;
			$this->generate_plan($offer, $course_time );

			$desc = get_field( 'descr', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params = [
				'hours'        => 'Продолжительность',
				'format'       => 'Форма обучения',
				'documents'    => 'Получаемые документы',
				'requirements' => 'Условия поступления',
				'for_who'      => 'Для кого это обучение?'
			];
			foreach ( $params as $key => $value ) {
				if ( 'hours' == $key ) {
					$v = ( get_field( $key, $post->ID, false ) ) ? get_field( $key, $post->ID, false ) : 9;
					$this->addParam( $offer, $value, $this->get_desc( $v ), 'час' );
				} else {

					$k = get_field( $key, $post->ID, false );
					$v = ($k)? $k: 'Нету данных';
					$this->addParam( $offer, $value, $this->get_desc( $v ) );
				}
			}

		}
	}

	public function generate_plan($offer, $time) {
		$time1 = ($time)?(int) $time/3: 10;
		$parameters = [
			[
				'name'  => 'План',
				'order' => '1',
				'unit'  => 'Вводный модуль',
				'hours' => $time1,
				'value' => 'Вводной'
			],
			[
				'name'  => 'План',
				'order' => '2',
				'unit'  => 'Модуль 1',
				'hours' => $time1,
				'value' => 'Основная часть'
			],
			[
				'name'  => 'План',
				'order' => '3',
				'unit'  => 'Модуль 2',
				'hours' => $time1,
				'value' => 'Основная 2 часть'
			]
		];
// Add each parameter as a child element of <params>
		foreach ( $parameters as $param ) {
			$parent = $offer->addChild( 'param', $param['value'] );
			$parent->addAttribute( 'name', $param['name'] );
			$parent->addAttribute( 'order', $param['order'] );
			$parent->addAttribute( 'unit', $param['unit'] );
			$parent->addAttribute( 'hours', $param['hours'] );
		}
	}

	public function addSeminarOffers( $seminar_posts ) {
		foreach ( $seminar_posts as $post ) {
			$offer = $this->offers->addChild( 'offer' );
			$offer->addAttribute( 'id', $post->ID );
			$offer->addChild( 'name', $post->post_title );
			$offer->addChild( 'url', get_permalink( $post->ID ) );
			$offer->addChild( 'categoryId', 0 );

			$category_id = 1000;

			$offer->addChild( 'categoryId', $category_id );
			$offer->addChild( 'currencyId', 'RUR' );
			$offer->addChild( 'price', 0 );
			$course_time = ( get_field( 'hours', $post->ID, false ) ) ? get_field( 'hours', $post->ID, false ) : 6;
			$this->generate_plan($offer, $course_time );


			$desc = get_field( 'meetup_more', $post->ID, false );
			$desc = $this->get_desc( $desc );
			$offer->addChild( 'description', $desc );
			$params     = [
				'meetup_date'     => 'Дата проведения',
				'date_end'        => 'Дата окончания',
				'time'            => 'Время',
				'meetup_speakers' => 'Спикеры',
				'meetup_address'  => 'Адрес мероприятия',
				'hours'           => 'Продолжительность',
			];
			$meetup_pic = get_field( 'meetup_pic', $post->ID, false );
			$offer->addChild( 'picture', $this->get_image( $meetup_pic ) );
			foreach ( $params as $key => $value ) {
				if ( 'meetup_date' == $key ) {
					$v         = get_field( $key, $post->ID, false );
					$v         = $this->get_desc( $v );
					$timestamp = strtotime( $v );
					$wp_date   = date_i18n( get_option( 'date_format' ), $timestamp );
					$this->addParam( $offer, $value, $this->get_desc( $wp_date ) );
				} elseif ( 'hours' == $key ) {
					$v = ( get_field( $key, $post->ID, false ) ) ? get_field( $key, $post->ID, false ) : 6;
					$this->addParam( $offer, $value, $this->get_desc( $v ), 'час' );
				} else {

					$k = get_field( $key, $post->ID, false );
					$v = ($k)? $k: 'Нету данных';
					$this->addParam( $offer, $value, $this->get_desc( $v ) );
				}
			}
		}
	}

	private function get_image( $image_id ) {
		$image_url = wp_get_attachment_url( $image_id );
		if ( $image_url ) {
			return $image_url;
		} else {
			return null;
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