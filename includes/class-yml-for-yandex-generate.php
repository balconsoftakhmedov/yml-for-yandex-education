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

		//	$this->shop = $this->xml->addChild( 'shop' );
		//	$this->setupShopInfo();
		//	$this->currencies = $this->shop->addChild( 'currencies' );
		//	$this->setupCurrencies();
		//	$this->sets = $this->shop->addChild( 'sets' );
		//	$this->setupSets();

			$this->offers = $this->shop->addChild( 'offers' );
		} catch ( Exception $e ) {

			echo 'Error: ' . $e->getMessage();
		}
	}

	private function setupShopInfo() {
		$this->shop->addChild( 'name', 'Школа Сэмпл.Курсы' );
		$this->shop->addChild( 'company', 'ООО Школа Сэмпл.Курсы' );
		$this->shop->addChild( 'url', 'https://courses.sample.s3.yandex.net' );
		$this->shop->addChild( 'email', 'support-courses@courses.sample.s3.yandex.net' );
		$this->shop->addChild( 'picture', 'https://avatars.mds.yandex.net/get-pdb/5679262/13d16a0c-27e9-4095-8f55-accdc2d7c8f0/s1200' );
		$this->shop->addChild( 'description', 'Онлайн школа по программированию и изучения языков' );
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




