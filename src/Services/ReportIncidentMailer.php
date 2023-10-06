<?php

namespace MediaWiki\Extension\ReportIncident\Services;

use MailAddress;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Mail\IEmailer;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Utils\UrlUtils;
use Psr\Log\LoggerInterface;
use StatusValue;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageValue;

/**
 * Handles emailing incident reports.
 */
class ReportIncidentMailer {

	private ServiceOptions $options;
	private TitleFactory $titleFactory;
	private ITextFormatter $textFormatter;
	private IEmailer $emailer;
	private LoggerInterface $logger;
	private UrlUtils $urlUtils;
	public const CONSTRUCTOR_OPTIONS = [
		'ReportIncidentRecipientEmails',
		'ReportIncidentEmailFromAddress',
	];

	/**
	 * @param ServiceOptions $options
	 * @param UrlUtils $urlUtils
	 * @param TitleFactory $titleFactory
	 * @param ITextFormatter $textFormatter
	 * @param IEmailer $emailer
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ServiceOptions $options,
		UrlUtils $urlUtils,
		TitleFactory $titleFactory,
		ITextFormatter $textFormatter,
		IEmailer $emailer,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->urlUtils = $urlUtils;
		$this->titleFactory = $titleFactory;
		$this->textFormatter = $textFormatter;
		$this->emailer = $emailer;
		$this->logger = $logger;
	}

	/**
	 * @param IncidentReport $incidentReport
	 * @return StatusValue
	 */
	public function sendEmail( IncidentReport $incidentReport ): StatusValue {
		$recipientEmails = $this->options->get( 'ReportIncidentRecipientEmails' );
		if ( !$recipientEmails || !is_array( $recipientEmails ) ) {
			$this->logger->warning( 'Not sending an email because ReportIncidentRecipientEmails is not defined.' );
			return StatusValue::newFatal(
				'rawmessage',
				'ReportIncidentRecipientEmails configuration is empty or not an array, not sending an email.'
			);
		}
		$to = array_map( static function ( $address ) {
			return new MailAddress( $address );
		}, $recipientEmails );

		if ( !$this->options->get( 'ReportIncidentEmailFromAddress' ) ) {
			$this->logger->warning( 'Not sending an email because ReportIncidentEmailFromAddress is not defined.' );
			return StatusValue::newFatal(
				'rawmessage',
				'ReportIncidentEmailFromAddress configuration is empty, not sending an email.'
			);
		}
		$from = new MailAddress(
			$this->options->get( 'ReportIncidentEmailFromAddress' ),
			$this->textFormatter->format(
				new MessageValue( 'emailsender' )
			)
		);
		$reportingUserPage = $this->titleFactory->newFromText(
			$incidentReport->getReportingUser()->getName(), NS_USER
		);
		$subject = $this->textFormatter->format(
			new MessageValue(
				'reportincident-email-subject',
				[ $reportingUserPage->getPrefixedDBkey() ]
			)
		);
		$revision = $incidentReport->getRevisionRecord();
		// In theory UrlUtils::expand() could return null, this seems pretty unlikely in practice;
		// cast to string to make Phan happy.
		$entrypointUrl = (string)$this->urlUtils->expand( wfScript() );
		$linkToPageAtRevision = wfAppendQuery(
			$entrypointUrl,
			[ 'oldid' => $revision->getId() ]
		);
		$reportedUserPage = $this->titleFactory->newFromText(
			$incidentReport->getReportedUser()->getName(), NS_USER
		);
		$behaviors = $incidentReport->getBehaviors();
		if ( $incidentReport->getSomethingElseDetails() ) {
			$somethingElseIndex = array_search( 'something-else', $behaviors );
			if ( $somethingElseIndex ) {
				$behaviors[$somethingElseIndex] = $this->textFormatter->format(
					new MessageValue(
						'reportincident-email-something-else',
						[ $incidentReport->getSomethingElseDetails() ]
					)
				);
			}
		}
		$emailUrl = $this->titleFactory->newFromText( 'Special:EmailUser' )
			->getSubpage( $reportingUserPage->getDBkey() )
			->getFullURL();

		$body = $this->textFormatter->format(
			new MessageValue(
				'reportincident-email-body',
				[
					$reportingUserPage->getDBkey(),
					$reportedUserPage->getDBkey(),
					$linkToPageAtRevision,
					implode( ', ', $behaviors ),
					$incidentReport->getDetails(),
					$emailUrl
				]
			)
		);
		return $this->emailer->send(
			$to,
			$from,
			$subject,
			$body
		);
	}
}
