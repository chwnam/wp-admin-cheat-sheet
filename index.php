<?php
/**
 * Plugin Name:             워드프레스 관리자 커스터마이즈 치트시트
 * Description:             관리자 여러 요소를 커스터마이즈 예제를 기록한 플러그인.
 * Author:                  changwoo
 * Author URI:              https://blog.changwoo.pe.kr
 * Plugin URI:              https://github.com/chwnam/wp-admin-cheat-sheet
 * Requires PHP:
 * Requires at least:
 * License: GPLv2 or later:
 * License URI:             https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WACS_MAIN = __FILE__;
const WACS_VERSION = '1.0.0';

require_once __DIR__ . '/includes/cpt.php';

