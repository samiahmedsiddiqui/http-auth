<?php
/**
 * HTTP Auth About.
 *
 * @package HTTPAuth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate about page HTML.
 */
class HTTP_Auth_About {

	/**
	 * Call Post Settings Function.
	 */
	public function __construct() {
		$this->more_plugins();
	}

	/**
	 * More Plugins HTML.
	 *
	 * @access private
	 * @since 0.1
	 *
	 * @return void
	 */
	private function more_plugins() {
		$img_src = plugins_url( '/assets/images', HTTP_AUTH_FILE );
		?>

		<div class="wrap">
			<div class="float">
				<h1>
		<?php
					esc_html_e(
						// translators: After `v` there will be a Plugin version.
						// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
						'HTTP Auth v' . HTTP_AUTH_VERSION,
						'http-auth'
					);
		?>
				</h1>
				<div class="tagline">
					<p>
					<?php
					esc_html_e(
						'Thank you for choosing HTTP Auth! We hope that your experience with our plugin for adding HTTP Authentication on your site is quick and easy.',
						'http-auth'
					);
					?>
					</p>
					<p>
					<?php
					esc_html_e(
						'To support future development and to help make it even better please leave a',
						'http-auth'
					);
					?>
					<a href="https://wordpress.org/support/plugin/http-auth/reviews/?rate=5#new-post" title="HTTP Auth Rating" target="_blank">
					<?php
					esc_html_e( '5-star', 'http-auth' );
					?>
					</a>
					<?php
					esc_html_e( 'rating with a nice message to me :)', 'http-auth' );
					?>
					</p>
				</div>
			</div>

			<div class="float">
				<object type="image/svg+xml" data="<?php echo esc_url( $img_src . '/http-auth.svg' ); ?>" width="128" height="128"></object>
			</div>

			<div class="product">
				<h2>
				<?php
				esc_html_e( 'More from Sami Ahmed Siddiqui', 'http-auth' );
				?>
				</h2>

				<span>
				<?php
				esc_html_e(
					'Our List of Plugins provides the services which helps you to manage your site URLs(Permalinks), Prevent your site from XSS Attacks, change absolute paths to relative, increase your site visitors by adding Structured JSON Markup and so on.',
					'http-auth'
				);
				?>
				</span>

				<div class="box recommended">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/custom-permalinks.svg' ); ?>" />
					</div>

					<h3>
					<?php
					esc_html_e( 'Custom Permalinks', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e( 'Custom Permalinks helps you to make your permalinks customized for individual posts, pages, tags or categories. It will NOT apply whole permalink structures, or automatically apply a category\'s custom permalink to the posts within that category.', 'http-auth' );
					?>
					</p>
					<a href="https://www.custompermalinks.com/" class="checkout-button" target="_blank">
						<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box recommended">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/prevent-xss-vulnerability.png' ); ?>" style="transform:scale(1.5)" />
					</div>

					<h3>
					<?php
					esc_html_e( 'Prevent XSS Vulnerability', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'Secure your site from the XSS Attacks so, your users won\'t lose any kind of information or not redirected to any other site by visiting your site with the malicious code in the URL or so. In this way, users can open their site URLs without any hesitation.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/prevent-xss-vulnerability/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/make-paths-relative.svg' ); ?>" />
					</div>

					<h3>
					<?php
					esc_html_e( 'Make Paths Relative', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'Convert the paths(URLs) to relative instead of absolute. You can make Post, Category, Archive, Image URLs and Script and Style src as per your requirement. You can choose which you want to be relative from the settings Page.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/make-paths-relative/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/schema-for-article.svg' ); ?>" />
					</div>

					<h3>
					<?php
					esc_html_e( 'SCHEMA for Article', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'Simply the easiest solution to add valid schema.org as a JSON script in the head of blog posts or articles. You can choose the schema either to show with the type of Article or NewsArticle from the settings page.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/schema-for-article/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/remove-links-and-scripts.svg' ); ?>" />
					</div>

					<h3>
					<?php
					esc_html_e( 'Remove Links and Scripts', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'It removes some meta data from the WordPress header so, your header keeps clean of useless information like shortlink, rsd_link, wlwmanifest_link, emoji_scripts, wp_embed, wp_json, emoji_styles, generator and so on.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/remove-links-and-scripts/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/media-post-permalink.png' ); ?>" style="transform:scale(1.5)" />
					</div>

					<h3>
					<?php
					esc_html_e( 'Media Post Permalink', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'On uploading  any image, let\'s say services.png, WordPress creates the attachment post with the permalink of /services/ and doesn\'t allow you to use that permalink to point your page. In this case, we come up with this great solution.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/media-post-permalink/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>

				<div class="box">
					<div class="img">
						<img src="<?php echo esc_url( $img_src . '/json-structuring-markup.svg' ); ?>" />
					</div>

					<h3>
					<?php
					esc_html_e( 'JSON Structuring Markup', 'http-auth' );
					?>
					</h3>
					<p>
					<?php
					esc_html_e(
						'Simply the easiest solution to add valid schema.org as a JSON script in the head of posts and pages. It provides you multiple SCHEMA types like Article, News Article, Organization and Website Schema.',
						'http-auth'
					);
					?>
					</p>
					<a href="https://wordpress.org/plugins/json-structuring-markup/" class="checkout-button" target="_blank">
					<?php esc_html_e( 'Check it out', 'http-auth' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
