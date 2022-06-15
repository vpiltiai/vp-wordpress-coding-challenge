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
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		$post_types = get_post_types( [ 'public' => true ] );
		$class_name = $attributes[ 'className' ];
		ob_start();
		?>
		<div class="<?php echo $class_name; ?>">
			<?php if ( $post_types ) : ?>
				<?php _e( 'Post Counts', 'site-counts' ); ?>
				<ul>
					<?php
					foreach ( $post_types as $post_type_slug ) {
						$post_type_object = get_post_type_object( $post_type_slug  );
						
						$posts_query_args = [
							'fields' => 'ids',
							'post_type' => $post_type_slug,
							'posts_per_page' => 10,
							'update_post_meta_cache' => false,
							'update_post_term_cache' => false,
						];

						$posts_query = new WP_Query( $posts_query_args );
						$posts_count = $posts_query->found_posts;
						wp_reset_query();
						
						echo '<li>' . __( 'There are', 'site-counts' ) . ' ' . $posts_count . ' ' . $post_type_object->labels->name . '.</li>';
					 } 
					 ?>
				</ul>
			<?php else : ?>
				<?php _e( 'Sorry, no found any posts.', 'site-counts' ); ?>
			<?php endif; ?>
			<p><?php echo __( 'The current post ID is ', 'site-counts' ) . get_queried_object_id() . '.'; ?></p>
			<?php
			$posts_to_exclude = [ get_queried_object_id ];
			$compare_time_posts_query_args = [
				'fields' => 'ids',
				'post_type' => [ 'post', 'page' ],
				'posts_per_page' => 5 + count( $posts_to_exclude ),
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'post_status' => 'any',
				'date_query' => [
					[
						'hour'		=> 9,
						'compare'	=> '>=',
					],
					[
						'hour' 		=> 17,
						'compare'	=> '<=',
					],
				],
				'tag' => 'foo',
				'category_name' => 'baz',
			];

			$compare_time_posts_query = new WP_Query( $compare_time_posts_query_args );

			if ( $compare_time_posts_query->have_posts() ) : ?>
				<h2><?php _e( '5 posts with the tag of foo and the category of baz', 'site-counts' ); ?></h2>
				<ul>
					<?php
						while ( $compare_time_posts_query->have_posts() ) {
							$compare_time_posts_query->the_post();

							if ( in_array( get_the_ID(), $posts_to_exclude ) ) {
								continue;
							}

							the_title( '<li>', '</li>');
						}
						wp_reset_query();
					?>
				</ul>
			<?php else: ?>
				<?php _e( 'Sorry, no found any posts.', 'site-counts' ); ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
