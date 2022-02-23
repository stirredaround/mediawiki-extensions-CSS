<?php
/**
 * CSS extension - A parser-function for adding CSS to articles via file,
 * article or inline rules.
 *
 * See https://www.mediawiki.org/wiki/Extension:CSS for installation and usage
 * details.
 *
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @author Rusty Burchfield
 * @copyright © 2007-2010 Aran Dunkley
 * @copyright © 2011 Rusty Burchfield
 * @license GPL-2.0-or-later
 */

class CSS {

	/**
	 * @param Parser &$parser
	 * @param string $css
	 * @return string
	 */
	public static function CSSRender( &$parser, $css ) {
		global $wgCSSPath, $wgStylePath, $wgCSSIdentifier, $wgCSSNotSanitizedNamespaceIDs;

		$css = trim( $css );
		if ( !$css ) {
			return '';
		}
		$title = Title::newFromText( $css );
		$rawProtection = "$wgCSSIdentifier=1";
		$headItem = '<!-- Begin Extension:CSS -->';

		if ( is_object( $title ) && $title->exists() ) {
			# Article actually in the db
			if ( is_array( $wgCSSNotSanitizedNamespaceIDs ) && in_array(
				$title->getNamespace(), $wgCSSNotSanitizedNamespaceIDs, true ) == false
			) {
				$headItem .= '<!-- Error in ' . substr( $css, 0, 30 ) . ( strlen( $css ) > 30 ? '...' : '' )
					. '. Only namespaces [' . implode( ',', $wgCSSNotSanitizedNamespaceIDs ) . '] allowed.'
					. ' You use: ' . $title->getNamespace() . ' (namespace id) -->';
			} else {
				$params = "action=raw&ctype=text/css&$rawProtection";
				$url = $title->getLocalURL( $params );
				$headItem .= Html::linkedStyle( $url );
			}
		} elseif ( $css[0] == '/' ) {
			# Regular file
			$base = $wgCSSPath === false ? $wgStylePath : $wgCSSPath;
			$url = wfAppendQuery( $base . $css, $rawProtection );

			# Verify the expanded URL is still using the base URL
			if ( strpos( wfExpandUrl( $url ), wfExpandUrl( $base ) ) === 0 ) {
				$headItem .= Html::linkedStyle( $url );
			} else {
				$headItem .= '<!-- Invalid/malicious path  -->';
			}
		} else {
			# sanitized user CSS
			$css = Sanitizer::checkCss( $css );

			$headItem .= Html::inlineStyle( $url, 'all', [ 'type' => 'text/css' ] );
		}

		$headItem .= '<!-- End Extension:CSS -->';
		$parser->getOutput()->addHeadItem( $headItem );
		return '';
	}

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'css', 'CSS::CSSRender' );
	}

	/**
	 * @param RawAction &$rawAction
	 * @param string &$text
	 * @return bool true
	 */
	public static function onRawPageViewBeforeOutput( &$rawAction, &$text ) {
		global $wgCSSIdentifier, $wgCSSNotSanitizedNamespaceIDs;

		if ( is_array( $wgCSSNotSanitizedNamespaceIDs ) == false && $rawAction->getRequest()
				->getBool( $wgCSSIdentifier ) ) {
			$text = Sanitizer::checkCss( $text );
		}
		return true;
	}
}
