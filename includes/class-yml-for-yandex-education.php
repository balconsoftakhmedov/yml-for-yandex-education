<?php

class YML_For_Yandex_Education extends YMLCatalogGenerator {

	public function __construct() {
		parent::__construct();

	}

	public function run() {

		$learning_posts = get_posts( array(
			'post_type'      => 'learning',
			'posts_per_page' =>  -1,
			'post_status'    => 'publish'
		) );
		$seminar_posts = get_posts( array(
			'post_type'      => 'seminars',
			'posts_per_page' =>  -1,
			'post_status'    => 'publish'
		) );
		$webinar_posts = get_posts( array(
			'post_type'      => 'webinars',
			'posts_per_page' =>  -1,
			'post_status'    => 'publish'
		) );
		// Добавляем предложения
		$this->addLearningOffers( $learning_posts );
		$this->addSeminarOffers( $seminar_posts );
		$this->addWebinarOffers( $webinar_posts );
		// Сохраняем XML-файл
		//$this->saveXMLFile( 'feed.xml' );
		//$this->saveXMLFile( 'feed.yml' );
		$this->saveXMLFile( 'feeds.xml' );
		$this->saveXMLFile( 'feeds.yml' );
		$this->saveXMLFile( 'feedme.xml' );
		$this->saveXMLFile( 'feedme.yml' );
	}
}