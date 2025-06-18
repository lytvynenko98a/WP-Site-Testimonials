<?php
/**
 * Plugin Name: Site Testimonials
 * Description: Форма на фронте для отправки отзывов. Отзывы сохраняются как Custom Post Type и отображаются в админке.
 * Author:      Yurii Lytvynenko
 * Version:     1.0.0
 * License:     GPL-2.0+
 * Text Domain: site-testimonials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Защита от прямого доступа.
}

class Site_Testimonials_Plugin {

	const CPT          = 'site_testimonial';
	const NONCE_ACTION = 'site_testimonial_submit';

	public function __construct() {

		// Регистрируем события.
		add_action( 'init',                          [ $this, 'register_post_type' ] );
		add_shortcode( 'site_testimonials_form',     [ $this, 'render_form_shortcode' ] );
		add_shortcode( 'site_testimonials',          [ $this, 'render_list_shortcode' ] );
		add_action( 'wp_enqueue_scripts',            [ $this, 'enqueue_assets' ] );
		add_filter( 'manage_edit-' . self::CPT . '_columns', [ $this, 'add_admin_columns' ] );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', [ $this, 'fill_admin_columns' ], 10, 2 );
	}

	/**
	 * Регистрируем Custom Post Type «Testimonials».
	 */
	public function register_post_type() {

		$labels = [
			'name'               => __( 'Testimonials', 'site-testimonials' ),
			'singular_name'      => __( 'Testimonial',  'site-testimonials' ),
			'add_new'            => __( 'Add New',      'site-testimonials' ),
			'add_new_item'       => __( 'Add New Testimonial', 'site-testimonials' ),
			'edit_item'          => __( 'Edit Testimonial', 'site-testimonials' ),
			'new_item'           => __( 'New Testimonial', 'site-testimonials' ),
			'all_items'          => __( 'All Testimonials', 'site-testimonials' ),
			'view_item'          => __( 'View Testimonial', 'site-testimonials' ),
			'search_items'       => __( 'Search Testimonials', 'site-testimonials' ),
			'not_found'          => __( 'No testimonials found', 'site-testimonials' ),
			'not_found_in_trash' => __( 'No testimonials found in Trash', 'site-testimonials' ),
			'menu_name'          => __( 'Testimonials', 'site-testimonials' ),
		];

		$args  = [
			'labels'             => $labels,
			'public'             => false,          // Не выводим на фронте как обычные записи.
			'show_ui'            => true,           // Но отображаем в админке.
			'show_in_menu'       => true,
			'supports'           => [ 'title', 'editor' ],
			'menu_icon'          => 'dashicons-testimonial',
			'capability_type'    => 'post',
		];

		register_post_type( self::CPT, $args );
	}

	/**
	 * Шорткод: вывод формы.
	 */
	public function render_form_shortcode( $atts, $content = '' ) {

		// Обработка POST запроса.
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['sitest_testimonial_submit'] ) ) {
			$this->handle_form_submit();
		}

		ob_start();
		?>

		<form class="sitest-form" method="post">
			<p>
				<label for="sitest_name"><?php _e( 'Ваше имя', 'site-testimonials' ); ?></label><br/>
				<input type="text" name="sitest_name" id="sitest_name" required>
			</p>

			<p>
				<label for="sitest_email"><?php _e( 'Email (не публикуется)', 'site-testimonials' ); ?></label><br/>
				<input type="email" name="sitest_email" id="sitest_email" required>
			</p>

			<p>
				<label for="sitest_message"><?php _e( 'Сообщение', 'site-testimonials' ); ?></label><br/>
				<textarea name="sitest_message" id="sitest_message" rows="6" required></textarea>
			</p>

			<?php wp_nonce_field( self::NONCE_ACTION, '_wpnonce_sitest' ); ?>
			<input type="hidden" name="sitest_testimonial_submit" value="1">
			<button type="submit"><?php _e( 'Send testimonial', 'site-testimonials' ); ?></button>
		</form>

		<?php
		return ob_get_clean();
	}

	/**
	 * Обработка отправки формы.
	 */
	private function handle_form_submit() {

		// Проверяем nonce и права.
		if ( ! isset( $_POST['_wpnonce_sitest'] ) || ! wp_verify_nonce( $_POST['_wpnonce_sitest'], self::NONCE_ACTION ) ) {
			return; // Неверный nonce – тихо выходим.
		}

		$name    = sanitize_text_field( wp_unslash( $_POST['sitest_name'] ?? '' ) );
		$email   = sanitize_email     ( wp_unslash( $_POST['sitest_email'] ?? '' ) );
		$message = wp_kses_post       ( wp_unslash( $_POST['sitest_message'] ?? '' ) );

		if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
			return; // Поля обязательны.
		}

		$post_id = wp_insert_post( [
			'post_type'    => self::CPT,
			'post_title'   => $name,
			'post_content' => $message,
			'post_status'  => 'pending', // На модерации.
		], true );

		if ( is_wp_error( $post_id ) ) {
			return; // Можно добавить обработку ошибок.
		}

		// Сохраняем email как метаполе.
		update_post_meta( $post_id, '_sitest_email', $email );

		// Уведомление админу (по желанию).
		wp_notify_postauthor( $post_id );

		// Перенаправляем с параметром успеха, чтобы избежать повторной отправки при F5.
		wp_safe_redirect( add_query_arg( 'testimonial_submitted', 'true', wp_get_referer() ?: home_url() ) );
		exit;
	}

	/**
	 * Шорткод: вывод опубликованных отзывов.
	 */
	public function render_list_shortcode( $atts, $content = '' ) {

		$atts = shortcode_atts(
			[
				'count' => 5,
				'order' => 'DESC',
			],
			$atts,
			'site_testimonials'
		);

		$q = new WP_Query( [
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['count'] ),
			'order'          => sanitize_key( $atts['order'] ),
		] );

		if ( ! $q->have_posts() ) {
			return '<p>' . __( 'No testimonials yet.', 'site-testimonials' ) . '</p>';
		}

		ob_start();
		echo '<div class="sitest-testimonials-list">';

		while ( $q->have_posts() ) : $q->the_post(); ?>
			<article class="sitest-item">
				<strong class="sitest-author">Имя автора: <?php the_title(); ?></strong>
				<div class="sitest-content">Сообщение: <?php the_content(); ?></div>
			</article>
		<?php endwhile;

		echo '</div>';
		wp_reset_postdata();

		return ob_get_clean();
	}

	/**
	 * Добавляем столбцы в таблицу «Testimonials».
	 */
	public function add_admin_columns( $columns ) {
		$columns['sitest_email'] = __( 'Email', 'site-testimonials' );
		return $columns;
	}

	public function fill_admin_columns( $column, $post_id ) {
		if ( 'sitest_email' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_sitest_email', true ) );
		}
	}

	/**
	 * Простенькие стили на фронте.
	 */
	public function enqueue_assets() {
		wp_register_style(
			'site-testimonials',
			plugins_url( 'assets/site-testimonials.css', __FILE__ ),
			[],
			'1.0.0'
		);
		wp_enqueue_style( 'site-testimonials' );
	}
}

new Site_Testimonials_Plugin();
