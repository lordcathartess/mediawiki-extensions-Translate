( function () {
	'use strict';

	/**
	 * Overrides the mw.uls.changeLanguage present in ULS
	 *
	 * @internal
	 * @param {string} language
	 */
	mw.uls.changeLanguage = function ( language ) {
		var page = 'Special:MyLanguage/' + mw.config.get( 'wgTranslateSourcePage' ),
			translationPageUrls = mw.config.get( 'wgTranslatePageTranslationUrls' ) || {},
			url = translationPageUrls[ language ];

		mw.uls.setLanguage( language ).then( function () {
			location.href = url || mw.util.getUrl( page );
		} );
	};
}() );
