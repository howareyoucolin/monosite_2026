<?php
/**
 * English / Chinese switching.
 *
 * A chapter carries a Chinese title and a Chinese body alongside its own (see
 * inc/chinese-version.php). This decides which of the two a request gets.
 *
 * Precedence: ?lang= in the URL, then the remembered preference, then English.
 *
 * The preference is kept in a cookie rather than localStorage on purpose. The
 * server has to know the language to render the page — the Chinese text lives
 * in post meta, not in the DOM — and localStorage is invisible to PHP, so a
 * returning reader would get an English page followed by a redirect or a
 * client-side swap. A cookie is on the request, so the first byte is already
 * right.
 *
 * @package kungfu_2026
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Query argument that switches language. */
define( 'AKW_LANG_PARAM', 'lang' );

/** Cookie that remembers the choice. */
define( 'AKW_LANG_COOKIE', 'akw_lang' );

/**
 * The languages the site serves.
 *
 * Keyed by the value that appears in ?lang=. 'cn' is a country code rather than
 * a language code, but it is what the URLs use, so it is what the keys are;
 * 'html' carries the correct language tag for the markup.
 *
 * Deliberately free of __(): resolving the current language reads this, and
 * translating a string asks which language is current. A translated label here
 * closes that loop.
 *
 * @return array[]
 */
function akw_languages() {
	return array(
		'en' => array( 'html' => 'en-US' ),
		'cn' => array( 'html' => 'zh-Hans' ),
	);
}

/**
 * The language a request gets when nothing says otherwise.
 *
 * @return string
 */
function akw_default_language() {
	return 'en';
}

/**
 * A language key, or an empty string if it is not one we serve.
 *
 * Unknown values resolve to nothing rather than to the default, so a mangled
 * ?lang= falls through to the remembered preference instead of overwriting it.
 *
 * @param mixed $value Candidate.
 * @return string
 */
function akw_normalize_language( $value ) {
	$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

	return isset( akw_languages()[ $value ] ) ? $value : '';
}

/**
 * The language this request is being served in.
 *
 * @return string A key of akw_languages().
 */
function akw_current_language() {
	static $language  = null;
	static $resolving = false;

	if ( null !== $language ) {
		return $language;
	}

	// Anything reached from here that translates a string would ask this
	// question again before it has an answer. Give that call the default rather
	// than recursing into it.
	if ( $resolving ) {
		return akw_default_language();
	}

	$resolving = true;

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading a display preference, not acting on one.
	$requested = isset( $_GET[ AKW_LANG_PARAM ] ) ? akw_normalize_language( wp_unslash( $_GET[ AKW_LANG_PARAM ] ) ) : '';
	// phpcs:enable

	$remembered = isset( $_COOKIE[ AKW_LANG_COOKIE ] ) ? akw_normalize_language( wp_unslash( $_COOKIE[ AKW_LANG_COOKIE ] ) ) : '';

	if ( $requested ) {
		$language = $requested;
	} elseif ( $remembered ) {
		$language = $remembered;
	} else {
		$language = akw_default_language();
	}

	$resolving = false;

	return $language;
}

/**
 * Whether the Chinese version is what this request should show.
 *
 * @return bool
 */
function akw_is_chinese() {
	return 'cn' === akw_current_language();
}

/**
 * Remember an explicit choice for a year.
 *
 * Only an explicit, valid ?lang= writes the cookie — that is what "unless they
 * choose again" means. Following an ordinary link changes nothing.
 */
function kungfu_2026_remember_language() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading a display preference, not acting on one.
	$chosen = isset( $_GET[ AKW_LANG_PARAM ] ) ? akw_normalize_language( wp_unslash( $_GET[ AKW_LANG_PARAM ] ) ) : '';
	// phpcs:enable

	if ( ! $chosen ) {
		return;
	}

	$known = isset( $_COOKIE[ AKW_LANG_COOKIE ] ) ? akw_normalize_language( wp_unslash( $_COOKIE[ AKW_LANG_COOKIE ] ) ) : '';

	if ( $known === $chosen ) {
		return;
	}

	// Keeps $_COOKIE and the browser in step within this same request.
	$_COOKIE[ AKW_LANG_COOKIE ] = $chosen;

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		AKW_LANG_COOKIE,
		$chosen,
		array(
			'expires'  => time() + YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN,
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);
}
add_action( 'init', 'kungfu_2026_remember_language' );

/**
 * Tell caches that the page body depends on the cookie.
 *
 * Without this a shared cache could hand a Chinese reader's page to the next
 * visitor asking for the same URL.
 *
 * @param array $headers Response headers.
 * @return array
 */
function kungfu_2026_vary_on_language( $headers ) {
	if ( is_admin() ) {
		return $headers;
	}

	$headers['Vary'] = empty( $headers['Vary'] ) ? 'Cookie' : $headers['Vary'] . ', Cookie';

	return $headers;
}
add_filter( 'wp_headers', 'kungfu_2026_vary_on_language' );

/**
 * The current URL, switched to a given language.
 *
 * @param string $lang Language key.
 * @return string Relative URL, keeping whatever else is in the query string.
 */
function akw_get_language_url( $lang ) {
	return add_query_arg( AKW_LANG_PARAM, $lang );
}

/**
 * The one link out of the language you are reading in.
 *
 * A single line of text rather than a pair of flags: a flag stands for a
 * country, not a language, and two of them side by side leave a reader working
 * out which one is the current state and which one is the button.
 *
 * The label names the language you would be switching *to*, so it reads as an
 * action. On the Chinese page it keeps "English" in Latin script, which is what
 * makes the way back findable by someone who cannot read the rest of the line.
 */
function akw_the_language_switcher() {
	$target = akw_is_chinese() ? 'en' : 'cn';

	$label = 'cn' === $target
		? __( 'Switch to Chinese version', 'kungfu_2026' )
		: __( 'Switch to English version', 'kungfu_2026' );

	$languages = akw_languages();
	?>
	<p class="lang-switch">
		<a
			class="lang-switch__link"
			href="<?php echo esc_url( akw_get_language_url( $target ) ); ?>"
			hreflang="<?php echo esc_attr( $languages[ $target ]['html'] ); ?>"
			rel="alternate"
		><?php echo esc_html( $label ); ?></a>
	</p>
	<?php
}

/**
 * Declare the page's language in the markup.
 *
 * @param string $output The lang attributes.
 * @return string
 */
function kungfu_2026_language_attributes( $output ) {
	if ( is_admin() ) {
		return $output;
	}

	$tag = akw_languages()[ akw_current_language() ]['html'];

	return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $tag ) . '"', $output );
}
add_filter( 'language_attributes', 'kungfu_2026_language_attributes' );

/**
 * Let the stylesheet see the language.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function kungfu_2026_language_body_class( $classes ) {
	$classes[] = 'lang-' . akw_current_language();

	return $classes;
}
add_filter( 'body_class', 'kungfu_2026_language_body_class' );

/**
 * Whether this request should swap a chapter's text for its Chinese version.
 *
 * Pages only. A REST request is not is_admin(), so without that check an editor
 * whose browser happens to hold the cookie would be served Chinese in
 * content.rendered — the block editor reads content.raw and would not be
 * damaged by it, but an API that answers differently depending on a display
 * cookie is a trap for anything else reading it.
 *
 * @return bool
 */
function akw_should_use_zh_text() {
	if ( is_admin() || is_feed() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	return akw_is_chinese();
}

/**
 * Show the Chinese title when there is one.
 *
 * Filtering the_title rather than editing templates covers every caller at
 * once — the chapter table, the single chapter, the document title — because
 * get_the_title() runs this filter.
 *
 * An untranslated chapter keeps its English title. A gap in the translation is
 * better read in English than as a blank row.
 *
 * @param string   $title   The title.
 * @param int|null $post_id Post the title belongs to.
 * @return string
 */
function kungfu_2026_chapter_title_zh( $title, $post_id = null ) {
	if ( ! $post_id || ! akw_should_use_zh_text() || AKW_CHAPTER !== get_post_type( $post_id ) ) {
		return $title;
	}

	$zh = akw_get_zh_title( $post_id );

	return '' !== $zh ? $zh : $title;
}
add_filter( 'the_title', 'kungfu_2026_chapter_title_zh', 10, 2 );

/**
 * Show the Chinese body when there is one.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function kungfu_2026_chapter_content_zh( $content ) {
	if ( ! akw_should_use_zh_text() ) {
		return $content;
	}

	$post = get_post();

	if ( ! $post || AKW_CHAPTER !== $post->post_type ) {
		return $content;
	}

	$zh = akw_get_zh_content( $post );

	return '' !== $zh ? $zh : $content;
}
add_filter( 'the_content', 'kungfu_2026_chapter_content_zh', 20 );

/**
 * The theme's own strings, in Chinese.
 *
 * A .mo file would be the usual answer, but this theme has no build step and
 * would need one to compile it. There are a dozen strings; a lookup keyed on
 * the English source is honest about that and stays readable.
 *
 * @return array<string, string>
 */
function akw_zh_strings() {
	return array(
		'Chapter'                    => '章',
		'Title'                      => '标题',
		'Arc'                        => '卷',
		'Chapters'                   => '章节',
		'No chapters published yet.' => '尚未发布章节。',
		'Nothing here yet.'          => '这里还没有内容。',
		'Chapter %d'                 => '第 %d 章',
		'Arc %1$d, Chapter %2$d'     => '第 %1$d 卷 第 %2$d 章',
		'Arc %1$s &middot; %2$s'     => '第 %1$s 卷 &middot; %2$s',
		'%s chapter'                 => '%s 章',
		'%s chapters'                => '%s 章',
		'Primary menu'               => '主菜单',
		'Primary Menu'               => '主菜单',
		'Switch to English version'  => '切换到 English 版',
		'Switch to Chinese version'  => '切换到中文版',
		'Total letter count: %s'     => '总字数：%s',
		'Copy Title + Content'       => '复制标题与正文',
		'Copied!'                    => '已复制！',
		'Chapter copied to clipboard' => '章节已复制到剪贴板',
		'Copy failed'                => '复制失败',
		'Next chapter'               => '下一章',
		'Back to top'                => '返回顶部',
		'&copy; %1$s, built by %2$s, all rights reserved.' => '&copy; %1$s，由 %2$s 制作，保留所有权利。',
	);
}

/**
 * Translate the theme's own strings on a Chinese page.
 *
 * Front end only, and only this theme's text domain — the editor stays in the
 * admin's own language.
 *
 * @param string $translation Current translation.
 * @param string $text        Source string.
 * @param string $domain      Text domain.
 * @return string
 */
function kungfu_2026_translate_zh( $translation, $text, $domain ) {
	if ( 'kungfu_2026' !== $domain || is_admin() || ! akw_is_chinese() ) {
		return $translation;
	}

	$strings = akw_zh_strings();

	return isset( $strings[ $text ] ) ? $strings[ $text ] : $translation;
}
add_filter( 'gettext', 'kungfu_2026_translate_zh', 10, 3 );

/**
 * The plural forms of the same.
 *
 * Chinese has one form, so both singular and plural resolve to the same entry.
 *
 * @param string $translation Current translation.
 * @param string $single      Singular source.
 * @param string $plural      Plural source.
 * @param int    $number      Count.
 * @param string $domain      Text domain.
 * @return string
 */
function kungfu_2026_translate_zh_plural( $translation, $single, $plural, $number, $domain ) {
	if ( 'kungfu_2026' !== $domain || is_admin() || ! akw_is_chinese() ) {
		return $translation;
	}

	$strings = akw_zh_strings();

	return isset( $strings[ $single ] ) ? $strings[ $single ] : $translation;
}
add_filter( 'ngettext', 'kungfu_2026_translate_zh_plural', 10, 5 );
