<?php

namespace MediaWiki\Extension\ReportIncident\Tests\Unit\Services;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentMailer;
use MediaWiki\Mail\IEmailer;
use MediaWiki\Message\TextFormatter;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @group ReportIncident
 *
 * @covers MediaWiki\Extension\ReportIncident\Services\ReportIncidentMailer
 */
class ReportIncidentMailerTest extends MediaWikiUnitTestCase {

	public function testNoRecipientEmails() {
		$reportIncidentMailer = $this->getReportIncidentMailer( [
			'ReportIncidentRecipientEmails' => null,
			'ReportIncidentEmailFromAddress' => null,
		] );
		$result = $reportIncidentMailer->sendEmail( $this->createMock( IncidentReport::class ) );
		$this->assertStatusError(
			'rawmessage',
			$result,
		);
		$this->assertSame(
			'ReportIncidentRecipientEmails configuration is empty or not an array, not sending an email.',
			$result->getErrors()[0]['params'][0]
		);
	}

	public function testInvalidRecipientEmailFormat() {
		$reportIncidentMailer = $this->getReportIncidentMailer( [
			'ReportIncidentRecipientEmails' => 'a@b.com',
			'ReportIncidentEmailFromAddress' => null,
		] );
		$result = $reportIncidentMailer->sendEmail( $this->createMock( IncidentReport::class ) );
		$this->assertStatusError(
			'rawmessage',
			$result,
		);
		$this->assertSame(
			'ReportIncidentRecipientEmails configuration is empty or not an array, not sending an email.',
			$result->getErrors()[0]['params'][0]
		);
	}

	public function testNoFromAddress() {
		$reportIncidentMailer = $this->getReportIncidentMailer( [
			'ReportIncidentRecipientEmails' => [ 'a@b.com' ],
			'ReportIncidentEmailFromAddress' => null,
		] );
		$result = $reportIncidentMailer->sendEmail( $this->createMock( IncidentReport::class ) );
		$this->assertStatusError(
			'rawmessage',
			$result,
		);
		$this->assertSame(
			'ReportIncidentEmailFromAddress configuration is empty, not sending an email.',
			$result->getErrors()[0]['params'][0]
		);
	}

	private function getReportIncidentMailer(
		array $options,
		?UrlUtils $urlUtils = null,
		?IEmailer $emailer = null,
		?TitleFactory $titleFactory = null,
		?TextFormatter $textFormatter = null,
		?LoggerInterface $logger = null
	) {
		return new ReportIncidentMailer(
			new ServiceOptions( ReportIncidentMailer::CONSTRUCTOR_OPTIONS, $options ),
			$urlUtils ?? $this->createMock( UrlUtils::class ),
			$titleFactory ?? $this->createMock( TitleFactory::class ),
			$textFormatter ?? $this->createMock( TextFormatter::class ),
			$emailer ?? $this->createMock( IEmailer::class ),
			$logger ?? $this->createMock( LoggerInterface::class )
		);
	}

}
