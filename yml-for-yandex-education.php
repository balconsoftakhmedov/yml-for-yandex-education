<?php
/**
 * Plugin Name: YML for Yandex Education
 * Plugin URI: https://flance.info/yml-for-yandex-education
 * Description: Плагин для работы с файлами YML для Яндекс.Образования
 * Version: 1.0.0
 * Author: Rusty
 * Author URI: www*
 * License: GPL2
 */

// Если этот файл вызван напрямую, выходим
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Определяем константу для пути к плагину
define( 'YML_FOR_YANDEX_EDUCATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Включаем основной класс плагина
require_once YML_FOR_YANDEX_EDUCATION_PLUGIN_PATH . 'includes/class-yml-for-yandex-generate.php';
require_once YML_FOR_YANDEX_EDUCATION_PLUGIN_PATH . 'includes/class-yml-for-yandex-education.php';


// Инициализируем плагин
function yml_for_yandex_education_init() {
    $plugin = new YML_For_Yandex_Education();
    $plugin->run();
}
add_action( 'init', 'yml_for_yandex_education_init' );
