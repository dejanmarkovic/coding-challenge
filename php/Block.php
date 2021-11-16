<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {

		error_log( 'inside register_block start' );

		register_block_type_from_metadata(
			$this->plugin->dir(),
				[
						'render_callback' => [ $this, 'render_callback' ],
				]
		);

		error_log( 'inside register_block end' );
	}


	/**
	 * Renders the block.
	 *
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( array $attributes, string $content, WP_Block $block ): string {

		$args = [
			'public' => true,
		];

		$output = 'objects';

		$post_type_objects = get_post_types( $args, $output );

		$css_class_name = '';
		if ( isset( $attributes['className'] ) ) {
			$css_class_name = $attributes['className'];
		}

		ob_start();

		?>

		<div class=" <?php echo esc_attr( $css_class_name ); ?>">
			<h2><?php esc_html_e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php

				foreach ( $post_type_objects as $post ) :

					$post_count_transient_key = 'xwp_post_count_' . $post->name;
					$post_count_per_type      = get_transient( $post_count_transient_key );

					if ( false === $post_count_per_type ) {

						$args = [
							'post_type'              => $post->name,
							'update_post_meta_cache' => false,
							'update_post_term_cache' => false,
						];

						if ( 'attachment' === $post->name ) {
							$args['post_status'] = 'inherit';
						}

						$post_count_query = new WP_Query( $args );
						$post_count       = $post_count_query->found_posts;

						set_transient( $post_count_transient_key, $post_count, 31536000 );
					}

					$post_count = get_transient( $post_count_transient_key );
					?>

					<li>
						<?php echo esc_html_x( 'There are ', 'site-counts', 'site-counts' ) . esc_html( $post_count ) . ' ' . esc_html( $post->labels->name ) . '.'; ?>
					</li>

					<?php

				endforeach;
				wp_reset_postdata();
				?>

			</ul>

			<p>
				<?php
				/* translators: current post ID */
				echo sprintf( esc_html_x( 'The current post ID is %d.', 'Current post ID', 'site-counts' ), get_the_ID() );
				?>
			</p>

			<?php

			$posts_to_exclude = [ get_the_ID() ];

			$recent_posts = new WP_Query(
				[
					'posts_per_page'         => 6,
					'post_type'              => [ 'post', 'page' ],
					'post_status'            => 'publish',
					'date_query'             => [
						[
							'hour'    => 9,
							'compare' => '>=',
						],
						[
							'hour'    => 17,
							'compare' => '<=',
						],
					],
					'tag'                    => 'foo',
					'category_name'          => 'baz',
					'update_post_meta_cache' => false,
					'include_children'       => false,
				]
			);

			if ( $recent_posts->found_posts ) :
				?>
			<h2><?php esc_html_e( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
			<ul>

				<?php

				$posts = 0;

				while ( $recent_posts->have_posts() && $posts < 5 ) :

					$recent_posts->the_post();

					$current = get_the_ID();
					if ( ! in_array( $current, $posts_to_exclude, true ) ) {
						$posts ++;
						the_title( '<li><a href="' . get_permalink() . '">', '</a></li>' );
					}

				endwhile;

			endif;

			?>
			</ul>
		</div>

		<?php

		wp_reset_postdata();
		return ob_get_clean();
	}
}
