<?php
/**
 * WACS: 커스텀 포스트 타입 등록
 *
 * 포스트 내용은 FakerPress 를 이용해 채워 넣으세요.
 *
 * @link https://wordpress.org/plugins/fakerpress/
 */

add_action( 'init', 'wacs_init_cpt' );

function wacs_init_cpt() {
	// 커스텀 포스트 등록.
	register_post_type(
		'wacs',
		[
			'label'               => 'wacs',
			'description'         => '',
			'public'              => true,
			'hierarchical'        => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_icon'           => 'dashicons-code-standards',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			'has_archive'         => true,
			'rewrite'             => [
				'slug'    => 'wacs',
				'feeds'   => true,
				'pages'   => true,
				'ep_mask' => EP_PERMALINK,
			],
			'query_var'           => true,
			'can_export'          => true,
			'delete_with_user'    => false,
			'show_in_rest'        => false,
		]
	);

	// 메타 필드 등록
	register_meta(
		'post',
		'_wacs_primary',
		[
			'object_subtype'    => 'wacs',
			'type'              => 'string',
			'description'       => '',
			'default'           => '',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => null,
			'show_in_rest'      => false,
		]
	);
}

// 스크린 종류에 따라 훅을 조절.
// 포스트 타입이 많아지면 불필요하게 액션/필터 걸리는 것을 막기 위한 의도.
add_action( 'current_screen', 'wacs_current_screen' );

function wacs_current_screen( WP_Screen $screen ) {
	if ( 'wacs' !== $screen->post_type ) {
		return;
	}

	if ( 'post' === $screen->base ) {
		// 포스트 편집 화면.
		// 메타 박스 등록.
		add_action( 'add_meta_boxes_wacs', 'wacs_add_meta_box' );
	} elseif ( 'edit' === $screen->base ) {
		// 포스트 목록 화면.

		// 커스텀 칼럼 추가.
		add_filter( "manage_{$screen->post_type}_posts_columns", 'wacs_custom_columns' );

		// 정렬 가능한 칼럼 추가.
		add_filter( "manage_{$screen->id}_sortable_columns", 'wacs_sortable_columns' );

		// 추가된 커스텀 칼럼에 값 출력.
		add_action( "manage_{$screen->post_type}_posts_custom_column", 'wacs_custom_column_values', 10, 2 );

		// 포스트의 커스텀 정렬 추가.
		add_action( 'pre_get_posts', 'wacs_cpt_pre_get_posts' );

		// 일괄 편집.
		add_action( 'bulk_edit_custom_box', 'wacs_bulk_edit', 10, 2 );

		// 빠른 편집.
		add_action( 'quick_edit_custom_box', 'wacs_quick_edit', 10, 3 );

		// 인라인 데이터 추가.
		add_action( 'add_inline_data', 'wacs_add_inline_data' );

		// 빠른-일괄 편집용 스크립트 추가.
		add_action( 'admin_enqueue_scripts', 'wacs_edit_scripts' );
	}

	// 메타값의 저장.
	add_action( 'save_post_wacs', 'wacs_save_post', 10, 3 );
}

// 메타 박스 등록.
function wacs_add_meta_box() {
	add_meta_box( 'wacs-props', 'WACS Properties', 'wacs_output_props' );
}

// 메타 박스 출력.
function wacs_output_props( WP_Post $post ) {
	?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="_wacs_primary">Primary</label>
            </th>
            <td>
                <input id="_wacs_primary" name="_wacs_primary" type="text" class="text large-text"
                       value="<?php echo esc_attr( get_post_meta( $post->ID, '_wacs_primary', true ) ); ?>">
                <p class="description">필드의 설명</p>
            </td>
        </tr>
    </table>
	<?php
	wp_nonce_field( 'wacs', '_wacs_nonce', false );
}

// 메타값의 저장.
function wacs_save_post( int $post_id ) {
	$updated = func_get_arg( 2 );
	$nonce   = wp_unslash( $_REQUEST['_wacs_nonce'] ?? '' );
	$skip    = filter_var( $_REQUEST['skip_primary'] ?? 'no', FILTER_VALIDATE_BOOLEAN ); // 벌크 편집에서 넘어옴.

	if ( $updated && ! $skip && wp_verify_nonce( $nonce, 'wacs' ) ) {
		// 'sanitize_callback' 파라미터에 지정한 콜백에서 사용자 입력을 세정.
		update_post_meta( $post_id, '_wacs_primary', wp_unslash( $_REQUEST['_wacs_primary'] ?? '' ) );
	}
}

// 커스텀 칼럼 추가.
function wacs_custom_columns( array $columns ): array {
	$date = $columns['date'] ?? '';
	unset( $columns['date'] );

	$columns['primary'] = 'Primary';
	$columns['date']    = $date;

	return $columns;
}

// 정렬 가능한 칼럼 추가.
function wacs_sortable_columns( array $columns ): array {
	// key: 커스텀 칼럼, val: GET 파라미터.
	$columns['primary'] = [ '_wacs_primary', true ];

	return $columns;
}

// 추가된 커스텀 칼럼에 값 출력.
function wacs_custom_column_values( string $column, int $post_id ) {
	if ( 'primary' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_wacs_primary', true ) );
	}
}

// 포스트의 커스텀 정렬 추가.
// 포스트의 메타 필드에서 검색 추가.
function wacs_cpt_pre_get_posts( WP_Query $query ) {
	$orderby = $query->get( 'orderby' );
	$order   = $query->get( 'order' );
	$s       = $query->get( 's' );

	if ( '_wacs_primary' === $orderby ) {
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', $order );
		$query->set( 'meta_key', '_wacs_primary' );
	}

	if ( $s ) {
		add_filter( 'posts_search', 'wacs_cpt_posts_search', 10, 2 );
		add_filter( 'posts_join', 'wacs_cpt_posts_join', 10, 2 );
		add_filter( 'posts_groupby', 'wacs_cpt_posts_group', 10, 2 );
	}
}

// 검색어를 메타 키에도 적용: $search 구문 조정.
function wacs_cpt_posts_search( string $search, WP_Query $query ): string {
	global $wpdb;

	// 구문 앞뒤로 공백은 항상 붙는다.
	if ( preg_match( '/^ AND \(\((.+)\)\) $/', $search, $matches ) ) {
		$q = $matches[1]; // 포스트 제목, 내용, 발췌 부분 검색 쿼리.
		$m = "";

		$terms = $query->get( 'search_terms' );
		foreach ( $terms as $term ) {
			$m .= $wpdb->prepare(
				' OR (wacs.meta_key=%s AND wacs.meta_value LIKE \'%%%s%%\')',
				'_wacs_primary',
				$term
			);
		}

		$m = trim( $m );

		$search = " AND ((($q) $m)) ";
	}

	return $search;
}

// 검색어를 메타 키에도 적용: JOIN 구문 조정.
function wacs_cpt_posts_join( string $join ): string {
	global $wpdb;

	$join .= " INNER JOIN $wpdb->postmeta wacs ON wacs.post_id = $wpdb->posts.ID ";

	return $join;
}

// 검색어를 메타 키에도 적용: GROUP BY 구문 조정.
function wacs_cpt_posts_group( string $group ): string {
	global $wpdb;

	if ( empty( $group ) ) {
		$group = "{$wpdb->posts}.ID";
	}

	return $group;
}

// 일괄 편집.
function wacs_bulk_edit( string $column_name, string $post_type ) {
	if ( 'primary' === $column_name && 'wacs' === $post_type ) {
		?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <div class="inline-edit-group wp-clearfix">
                    <label class="alignleft" for="bulk_wacs_primary">
                        <span class="title">Primary</span>
                    </label>
                    <div class="primary-wrap">
                        <input id="bulk_wacs_primary" name="_wacs_primary" type="text" class="text" value="">
                        <label for="skip-primary" id="label-skip-primary">
                            <input id="skip-primary" name="skip_primary" type="checkbox" class="text" value="yes">
                            값을 변경하지 않음
                        </label>
                    </div>
                </div>
        </fieldset>
		<?php
		wp_nonce_field( 'wacs', '_wacs_nonce', false );
	}
}

// 빠른 편집.
function wacs_quick_edit( string $column_name, string $post_type, string $taxonomy ) {
	if ( 'primary' === $column_name && 'wacs' === $post_type ) {
		?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <div class="inline-edit-group wp-clearfix">
                    <label class="alignleft" for="quick_wacs_primary">
                        <span class="title">Primary</span>
                    </label>
                    <div class="primary-wrap">
                        <input id="quick_wacs_primary" name="_wacs_primary" type="text" class="text" value="">
                    </div>
                </div>
        </fieldset>
		<?php
		wp_nonce_field( 'wacs', '_wacs_nonce', false );
	}
}

// 인라인 데이터 추가: 빠른 편집에 사용됨.
function wacs_add_inline_data( WP_Post $post ) {
	if ( 'wacs' === $post->post_type ) {
		echo '<div class="wacs_primary">' . esc_html( get_post_meta( $post->ID, '_wacs_primary', true ) ) . '</div>';
	}
}

// 빠른/일괄 편집용 스크립트 추가.
function wacs_edit_scripts() {
	if ( ! wacs_is_fake_screen() ) {
		wp_register_script(
			'wacs-edit',
			plugins_url( 'includes/quick-bulk-edit.js', WACS_MAIN ),
			[ 'jquery' ],
			WACS_VERSION,
			true
		);

		wp_enqueue_script( 'wacs-edit' );

		wp_enqueue_style( 'wacs-edit', plugins_url( 'includes/quick-bulk-edit.css', WACS_MAIN ) );
	}
}


// NONCE 체크 성공 후, 스크린 옵션 강제 활성화
if ( wp_doing_ajax() && 'inline-save' === ( $_REQUEST['action'] ?? '' ) ) {
	add_action( 'check_ajax_referer', 'wacs_ajax_referer', 10, 2 );

	function wacs_ajax_referer( string $action, bool $result ) {
		if (
			$result &&
			'inlineeditnonce' === $action &&
			'wacs' === ( $_REQUEST['post_type'] ?? '' )
		) {
			define( 'WACS_FAKE_SCREEN', true );
			set_current_screen( 'edit-wacs' );
		}
	}
}


function wacs_is_fake_screen(): bool {
	return defined( 'WACS_FAKE_SCREEN' ) && WACS_FAKE_SCREEN;
}