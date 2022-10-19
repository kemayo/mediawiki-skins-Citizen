<?php
/**
 * Citizen - A responsive skin developed for the Star Citizen Wiki
 *
 * This file is part of Citizen.
 *
 * Citizen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Citizen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Citizen.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup Skins
 */

namespace MediaWiki\Skins\Citizen;

use MediaWiki\Skins\Citizen\Partials\BodyContent;
use MediaWiki\Skins\Citizen\Partials\Drawer;
use MediaWiki\Skins\Citizen\Partials\Footer;
use MediaWiki\Skins\Citizen\Partials\Header;
use MediaWiki\Skins\Citizen\Partials\Metadata;
use MediaWiki\Skins\Citizen\Partials\PageTools;
use MediaWiki\Skins\Citizen\Partials\Tagline;
use MediaWiki\Skins\Citizen\Partials\Theme;
use MediaWiki\Skins\Citizen\Partials\Title;
use SkinMustache;

/**
 * Skin subclass for Citizen
 * @ingroup Skins
 */
class SkinCitizen extends SkinMustache {
	use GetConfigTrait;

	/**
	 * Overrides template, styles and scripts module
	 *
	 * @inheritDoc
	 */
	public function __construct( $options = [] ) {
		// Add skin-specific features
		$this->buildSkinFeatures( $options );
		parent::__construct( $options );
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateData(): array {
		$data = [];
		$title = $this->getOutput()->getTitle();
		$parentData = parent::getTemplateData();

		$header = new Header( $this );
		$drawer = new Drawer( $this );
		$pageTitle = new Title( $this );
		$tagline = new Tagline( $this );
		$bodycontent = new BodyContent( $this );
		$footer = new Footer( $this );
		$tools = new PageTools( $this );

		// Naming conventions for Mustache parameters.
		//
		// Value type (first segment):
		// - Prefix "is" or "has" for boolean values.
		// - Prefix "msg-" for interface message text.
		// - Prefix "html-" for raw HTML.
		// - Prefix "data-" for an array of template parameters that should be passed directly
		//   to a template partial.
		// - Prefix "array-" for lists of any values.
		//
		// Source of value (first or second segment)
		// - Segment "page-" for data relating to the current page (e.g. Title, WikiPage, or OutputPage).
		// - Segment "hook-" for any thing generated from a hook.
		//   It should be followed by the name of the hook in hyphenated lowercase.
		//
		// Conditionally used values must use null to indicate absence (not false or '').

		$data += [
			// Booleans
			'toc-enabled' => !empty( $parentData['data-toc'] ),
			// Data objects
			'data-sitestats' => $drawer->getSiteStatsData(),
			'data-user-info' => $header->getUserInfoData( $parentData['data-portlets']['data-user-page'] ),
			// HTML strings
			'html-title-heading--formatted' => $pageTitle->buildTitle( $parentData, $title ),
			'html-citizen-jumptotop' => $parentData['msg-citizen-jumptotop'] . ' [home]',
			'html-body-content--formatted' => $bodycontent->buildBodyContent(),
			'html-tagline' => $tagline->getTagline(),
			// Messages
			// Needed to be parsed here as it should be wikitext
			'msg-citizen-footer-desc' => $this->msg( "citizen-footer-desc" )->inContentLanguage()->parse(),
			'msg-citizen-footer-tagline' => $this->msg( "citizen-footer-tagline" )->inContentLanguage()->parse(),
			// Decorate data provided by core
			'data-search-box' => $header->decorateSearchBoxData( $parentData['data-search-box'] ),
			'data-portlets-sidebar' => $drawer->decorateSidebarData( $parentData['data-portlets-sidebar'] ),
			'data-footer' => $footer->decorateFooterData( $parentData['data-footer'] ),
		];

		$data += $tools->getPageToolsData( $parentData );

		// Show some portlet labels
		// NOTE: This is only placed here temporarily
		if ( $parentData['data-portlets']['data-variants']['is-empty'] === false ) {
			$parentData['data-portlets']['data-variants']['has-label'] = true;
		}

		return array_merge( $parentData, $data );
	}

	/**
	 * Change access to public, as it is used in partials
	 *
	 * @param Title $title
	 * @param string $html body text
	 * @return string
	 */
	final public function wrapHTMLPublic( $title, $html ) {
		return parent::wrapHTML( $title, $html );
	}

	/**
	 * @inheritDoc
	 *
	 * Manually disable some site-wide tools in TOOLBOX
	 * They are re-added in the drawer
	 *
	 * TODO: Remove this hack when Desktop Improvements separate page and site tools
	 *
	 * @return array
	 */
	protected function buildNavUrls() {
		$urls = parent::buildNavUrls();

		$urls['upload'] = false;
		$urls['specialpages'] = false;

		return $urls;
	}

	/**
	 * Set up optional skin features
	 *
	 * @param array &$options
	 */
	private function buildSkinFeatures( array &$options ) {
		$title = $this->getOutput()->getTitle();

		$metadata = new Metadata( $this );
		$skinTheme = new Theme( $this );

		// Add metadata
		$metadata->addMetadata();

		// Add theme handler
		$skinTheme->setSkinTheme( $options );

		// Disable default ToC since it is handled by Citizen
		$options['toc'] = false;

		// Collapsible sections
		// Load in content pages
		if ( $title !== null && $title->isContentPage() ) {
			// Load Citizen collapsible sections modules if enabled
			if ( $this->getConfigValue( 'CitizenEnableCollapsibleSections' ) === true ) {
				$options['scripts'][] = 'skins.citizen.scripts.sections';
				$options['styles'][] = 'skins.citizen.styles.sections';
				$options['styles'][] = 'skins.citizen.icons.sections';
			}
		}

		// CJK fonts
		if ( $this->getConfigValue( 'CitizenEnableCJKFonts' ) === true ) {
			$options['styles'][] = 'skins.citizen.styles.fonts.cjk';
		}

		// Drawer sitestats
		if ( $this->getConfigValue( 'CitizenEnableDrawerSiteStats' ) === true ) {
			$options['styles'][] = 'skins.citizen.styles.sitestats';
		}

		// Drawer subsearch
		if ( $this->getConfigValue( 'CitizenEnableDrawerSubSearch' ) === true ) {
			$options['scripts'][] = 'skins.citizen.scripts.drawer';
		}

		// Debug styles
		if (
			$this->getConfigValue( 'ShowDebug' ) === true
			|| $this->getConfigValue( 'ShowExceptionDetails' ) === true
		) {
			$options['styles'][] = 'skins.citizen.styles.debug';
		}
	}
}
