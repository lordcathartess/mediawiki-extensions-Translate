<?php
declare( strict_types = 1 );

use MediaWiki\Extension\Translate\MessageLoading\MessageHandle;
use MediaWiki\Extension\Translate\PageTranslation\TranslatablePage;
use MediaWiki\Extension\Translate\Services;
use MediaWiki\Title\Title;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @covers \MediaWiki\Extension\Translate\PageTranslation\TranslatablePage
 * @covers \MediaWiki\Extension\Translate\PageTranslation\TranslatablePageMarker
 * @group Database
 */
class TranslatablePageIntegrationTest extends MediaWikiIntegrationTestCase {
	use MessageGroupTestTrait;
	use TranslatablePageTestTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->setupGroupTestEnvironment( $this );
	}

	public function testIsSourcePage() {
		$translatablePage = $this->createMarkedTranslatablePage(
			'Test page', 'Testing page', $this->getTestSysop()->getUser()
		);

		$this->assertTrue( TranslatablePage::isSourcePage( $translatablePage->getTitle() ) );

		Services::getInstance()->getTranslatablePageMarker()->unmarkPage(
			$translatablePage,
			$this->getTestSysop()->getUser(),
			false
		);
		$this->getServiceContainer()->getMainWANObjectCache()->clearProcessCache();
		$this->setService( 'LocalServerObjectCache', new HashBagOStuff() );

		$this->assertFalse( TranslatablePage::isSourcePage( $translatablePage->getTitle() ) );

		$this->resetServices();
	}

	public function testCustomTranslationPageTitleIsRecognized(): void {
		$user = $this->getTestSysop()->getUser();
		$sourcePage = $this->createMarkedTranslatablePage(
			'Source page',
			'Translated source text',
			$user
		);
		$customTitle = Title::newFromText( 'Custom German page' );
		$sourcePage->setTranslationPageTitle( 'de', $customTitle );

		$editStatus = $this->editPage(
			$customTitle,
			ContentHandler::makeContent( 'German text', $customTitle ),
			__METHOD__,
			NS_MAIN,
			$user
		);
		$revisionId = $editStatus->value['revision-record']->getId();
		Services::getInstance()->getRevTagStore()->setTranslationPageMapping(
			$customTitle,
			$sourcePage->getTitle()->getArticleID(),
			'de',
			$revisionId
		);

		$resolvedPage = TranslatablePage::isTranslationPage( $customTitle );
		$this->assertInstanceOf( TranslatablePage::class, $resolvedPage );
		$this->assertSame( 'de', ( new MessageHandle( $customTitle ) )->getCode() );
		$this->assertTrue(
			( new MessageHandle( $customTitle ) )->getTitleForBase()->equals( $sourcePage->getTitle() )
		);
		$this->assertContains(
			$customTitle->getPrefixedText(),
			array_map(
				static fn ( Title $title ) => $title->getPrefixedText(),
				$sourcePage->getTranslationPages()
			)
		);
		$this->assertSame( 'de', $resolvedPage->getTargetLanguageCode() );
	}
}
