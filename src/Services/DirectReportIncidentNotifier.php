<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\ReportIncident\Services;

use MediaWiki\Config\Config;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Mail\IEmailer;
use MediaWiki\Mail\MailAddress;
use MediaWiki\MainConfigNames;
use MediaWiki\Status\Status;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\Utils\UrlUtils;
use Psr\Log\LoggerInterface;
use StatusValue;
use Wikimedia\Message\ITextFormatter;
use Wikimedia\Message\MessageValue;

/**
 * Client class to send direct reports to community-configured emails
 */
class DirectReportIncidentNotifier implements IReportIncidentNotifier {
	public const CONSTRUCTOR_OPTIONS = [
		'Script'
	];

	public function __construct(
		private readonly Config $mainConfig,
		private readonly Config $communityConfig,
		private readonly ServiceOptions $serviceOptions,
		private readonly LoggerInterface $logger,
		private readonly IEmailer $emailer,
		private readonly ITextFormatter $textFormatter,
		private readonly UrlUtils $urlUtils,
		private readonly TitleFactory $titleFactory,
		private readonly UserFactory $userFactory,
	) {
		$serviceOptions->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * @param IncidentReport $incidentReport
	 * @return string email from community configuration
	 */
	public function getSendToEmail( IncidentReport $incidentReport ): string {
		$behaviorCategory = $incidentReport->getBehaviorType();
		switch ( $behaviorCategory ) {
			case 'doxing':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Doxing_HelpMethod' )
					->Email;
			case 'hate-or-discrimination':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_HateSpeech_HelpMethod' )
					->Email;
			case 'intimidation':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Intimidation_HelpMethod' )
					->Email;
			case 'sexual-harassment':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_SexualHarassment_HelpMethod' )
					->Email;
			case 'spam':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Spam_HelpMethod' )
					->Email;
			case 'trolling':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Trolling_HelpMethod' )
					->Email;
			case 'something-else':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_SomethingElse_HelpMethod' )
					->Email;
			case 'sockpuppetry':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Sockpuppetry_HelpMethod' )
					->Email;
			case 'vandalism':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Vandalism_HelpMethod' )
					->Email;
			case 'userdispute':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_UserDispute_HelpMethod' )
					->Email;
			case 'disruptiveediting':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_DisruptiveEditing_HelpMethod' )
					->Email;
			case 'other':
				return $this->communityConfig
					->get( 'ReportIncident_NonEmergency_Other_HelpMethod' )
					->Email;
			default:
				return '';
		}
	}

	/**
	 * Send the report to an email
	 * @param IncidentReport $incidentReport
	 * @return StatusValue
	 */
	public function notify( IncidentReport $incidentReport ): StatusValue {
		$reportingUser = $incidentReport->getReportingUser();
		$reportedUser = $incidentReport->getReportedUser();
		$behaviorCategory = $incidentReport->getBehaviorType();
		$emailUserLink = $this->titleFactory
			->newFromTextThrow( 'EmailUser', NS_SPECIAL )
			->getFullURL( [ 'wpTarget' => $reportingUser->getName() ], false, PROTO_HTTPS );
		[ $linkPrefixText, $linkToPageAtRevision ] = $this->getLinkToReportedContent( $incidentReport );

		// Generate the email body with the following parameters passed through:
		// 1. reporter
		// 2. reported (if available)
		// 3-4. link to revision/thread (if available)
		// 5. behavior being reported
		// 6. details
		// 7. link to reporter's Special:EmailUser
		$body = $this->textFormatter->format(
			new MessageValue(
				'reportincident-directreport-email-body',
				[
					$reportingUser->getName(),
					$reportedUser ? $reportedUser->getName() : '',
					$linkPrefixText,
					$linkToPageAtRevision,
					// Possible message keys used here:
					// * reportincident-dialog-harassment-type-doxing
					// * reportincident-dialog-harassment-type-hate-or-discrimination
					// * reportincident-dialog-harassment-type-intimidation
					// * reportincident-dialog-harassment-type-sexual-harassment
					// * reportincident-dialog-harassment-type-spam
					// * reportincident-dialog-harassment-type-trolling
					// * reportincident-dialog-harassment-type-something-else
					// * reportincident-dialog-harassment-type-sockpuppetry
					// * reportincident-dialog-harassment-type-vandalism
					// * reportincident-dialog-harassment-type-disruptiveediting
					// * reportincident-dialog-harassment-type-other
					new MessageValue( "reportincident-dialog-harassment-type-$behaviorCategory" ),
					$incidentReport->getDirectReport() ?? '',
					$emailUserLink,
				]
			)
		);

		// This email is being sent by MediaWiki on behalf of the reporter.
		$passwordSender = $this->mainConfig->get( MainConfigNames::PasswordSender );
		$sender = new MailAddress(
			$passwordSender,
			$this->textFormatter->format( new MessageValue( 'emailsender' ) )
		);
		$sendTo = $this->getSendToEmail( $incidentReport );
		if ( !$sendTo ) {
			$this->logger->error(
				'Direct report failed to send, no email configured'
			);
			return StatusValue::newFatal( 'reportincident-directreport-featuredisabled-error' );
		}
		$sendTo = new MailAddress( $sendTo );
		$subject = $this->textFormatter->format( new MessageValue(
			'reportincident-directreport-email-subject',
				[
					$reportingUser->getName(),
					date( 'Y-m-d' ),
					date( 'H:i' )
				]
		) );

		$reporterEmail = $this->userFactory
			->newFromUserIdentity( $incidentReport->getReportingUser() )
			->getEmail();

		$sendEmailStatus = Status::wrap(
			$this->emailer->send(
				$sendTo, $sender, $subject, $body, null, [ 'replyTo' => new MailAddress( $reporterEmail ) ]
			)
		);

		if ( !$sendEmailStatus->isOK() ) {
			$this->logger->error(
				'Direct report failed to send, mailer failed',
				[ 'errors' => $sendEmailStatus->getErrorsArray() ]
			);
			return StatusValue::newFatal( 'reportincident-directreport-send-error', $sendTo->toString() );
		}
		$this->logger->info( 'Direct report sent' );
		return StatusValue::newGood();
	}

	/**
	 * Gets a link to the reported content. This returns a link to
	 * the permalink of the page, and if the report entry point was
	 * via DiscussionTools the link includes an anchor to the topic
	 * or comment that was reported.
	 *
	 * @param IncidentReport $incidentReport The IncidentReport object provided to ::sendEmail
	 * @return array First item being the prefix text for the link to be used in the email
	 *   and the second item being the URL to the reported content.
	 */
	private function getLinkToReportedContent( IncidentReport $incidentReport ): array {
		$linkPrefixText = new MessageValue( 'reportincident-notification-link-to-page-prefix' );
		$revision = $incidentReport->getRevisionRecord();

		if ( $revision === null ) {
			$title = $this->titleFactory->newFromPageReference( $incidentReport->getPage() );
			return [ $linkPrefixText, $title->getFullURL( '', false, PROTO_HTTPS ) ];
		}

		// In theory UrlUtils::expand() could return null, this seems pretty unlikely in practice;
		// cast to string to make Phan happy.
		$entrypointUrl = (string)$this->urlUtils->expand( $this->serviceOptions->get( 'Script' ) );
		$linkToPageAtRevision = wfAppendQuery(
			$entrypointUrl,
			[ 'oldid' => $revision->getId() ]
		);
		$threadId = $incidentReport->getThreadId();
		if ( $threadId ) {
			// If a thread ID is defined, then add it to the link to the page at revision
			// as an anchor in the URL. Currently this is only provided by DiscussionTools.
			$linkToPageAtRevision .= '#' . urlencode( $threadId );
			// If the threadId starts with 'h-', then the threadId refers to a topic/section header
			// and as such the link prefix text should indicate this instead of saying it is a comment.
			if ( substr( $threadId, 0, 2 ) === 'h-' ) {
				$linkPrefixText = new MessageValue( 'reportincident-notification-link-to-topic-prefix' );
			} else {
				$linkPrefixText = new MessageValue( 'reportincident-notification-link-to-comment-prefix' );
			}
		}

		return [ $linkPrefixText, $linkToPageAtRevision ];
	}
}
