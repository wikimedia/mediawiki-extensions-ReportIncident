<?php

namespace MediaWiki\Extension\ReportIncident\Api\Rest\Handler;

use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Config\Config;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Language\Language;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\TokenAwareHandlerTrait;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\User\UserNameUtils;
use Psr\Log\LoggerInterface;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * REST handler for /reportincident/v0/report
 */
class ReportHandler extends SimpleHandler {

	use TokenAwareHandlerTrait;

	private Config $config;
	private ReportIncidentManager $reportIncidentManager;
	private RevisionStore $revisionStore;
	private UserNameUtils $userNameUtils;
	private UserIdentityLookup $userIdentityLookup;
	private LoggerInterface $logger;
	private UserFactory $userFactory;
	private Language $contentLanguage;

	public const HTTP_STATUS_FORBIDDEN = 403;
	public const HTTP_STATUS_NOT_FOUND = 404;

	public function __construct(
		Config $config,
		RevisionStore $revisionStore,
		UserNameUtils $userNameUtils,
		UserIdentityLookup $userIdentityLookup,
		ReportIncidentManager $reportIncidentManager,
		UserFactory $userFactory,
		Language $contentLanguage
	) {
		$this->config = $config;
		$this->reportIncidentManager = $reportIncidentManager;
		$this->revisionStore = $revisionStore;
		$this->userNameUtils = $userNameUtils;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->logger = LoggerFactory::getInstance( 'ReportIncident' );
		$this->userFactory = $userFactory;
		$this->contentLanguage = $contentLanguage;
	}

	/**
	 * @throws LocalizedHttpException
	 */
	public function run(): Response {
		if ( !$this->config->get( 'ReportIncidentApiEnabled' ) ) {
			// Pretend the route doesn't exist if the feature flag is off.
			throw new LocalizedHttpException(
				new MessageValue( 'rest-no-match' ),
				self::HTTP_STATUS_NOT_FOUND
			);
		}

		$userIdentity = $this->getAuthority()->getUser();
		$this->validateUserCanSubmitReport( $userIdentity );

		$incidentReport = $this->getIncidentReportObjectFromValidatedBody( $this->getValidatedBody() );
		$this->authorizeIncidentReport( $userIdentity );

		return $this->submitIncidentReport( $incidentReport );
	}

	/**
	 * Validates that a user can submit an incident report using the API.
	 * If the validation fails, a LocalizedHttpException will be thrown.
	 *
	 * @param UserIdentity $userIdentity The UserIdentity associated with the authority.
	 * @return void The method will return nothing if validation succeeds (and error otherwise).
	 * @throws LocalizedHttpException If validation fails
	 */
	private function validateUserCanSubmitReport( UserIdentity $userIdentity ): void {
		$user = $this->userFactory->newFromUserIdentity( $userIdentity );

		if ( !$user->isNamed() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-permission-denied-anon' ),
				self::HTTP_STATUS_FORBIDDEN
			);
		}

		if ( $user->getEditCount() === 0 ) {
			$this->logger->warning(
				'User "{user}" with zero edits attempted to perform "reportincident".',
				[ 'user' => $this->getAuthority()->getUser()->getName() ]
			);
			throw new LocalizedHttpException(
				new MessageValue( 'apierror-permissiondenied', [ new MessageValue( 'action-reportincident' ) ] ),
				self::HTTP_STATUS_FORBIDDEN
			);
		}

		// Prevent users targeted by any block, including unrelated partial blocks, from submitting reports.
		// (T378778)
		if ( $user->getBlock() ) {
			$this->logger->warning(
				'Blocked user "{user}" attempted to perform "reportincident".',
				[ 'user' => $this->getAuthority()->getUser()->getName() ]
			);
			throw new LocalizedHttpException(
				new MessageValue( 'apierror-blocked' ),
				self::HTTP_STATUS_FORBIDDEN
			);
		}

		$isDeveloperMode = $this->config->get( 'ReportIncidentDeveloperMode' );

		$now = (int)ConvertibleTimestamp::now();
		$registrationTime = (int)$user->getRegistration();
		$reportIncidentMinimumAccountAgeInSeconds = $this->config->get( 'ReportIncidentMinimumAccountAgeInSeconds' );
		if ( $registrationTime &&
			$reportIncidentMinimumAccountAgeInSeconds &&
			!$isDeveloperMode &&
			( ( $now - $registrationTime ) < $reportIncidentMinimumAccountAgeInSeconds ) ) {
			$this->logger->warning(
				'User "{user}" whose account is under wgReportIncidentMinimumAccountAgeInSeconds' .
				' threshold attempted to perform "reportincident".',
				[ 'user' => $this->getAuthority()->getUser()->getName() ]
			);
			throw new LocalizedHttpException(
				new MessageValue( 'apierror-permissiondenied', [ new MessageValue( 'action-reportincident' ) ] ),
				self::HTTP_STATUS_FORBIDDEN
			);
		}

		if ( !$isDeveloperMode && !$user->isEmailConfirmed() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'reportincident-confirmedemail-required' ), 403
			);
		}
	}

	/**
	 * Gets the IncidentReport object from the request body
	 * after performing validation on the request data. If
	 * validation fails a LocalizedHttpException will be
	 * thrown.
	 *
	 * @param mixed $body The value of $this->getValidatedBody()
	 * @return IncidentReport If the validation succeeds
	 * @throws LocalizedHttpException If the validation fails
	 */
	private function getIncidentReportObjectFromValidatedBody( $body ): IncidentReport {
		// Validate the CSRF token in the request body.
		$this->validateToken();
		// Validate that the revision with the given ID exists.
		$revisionId = $body['revisionId'];
		$revision = $this->revisionStore->getRevisionById( $revisionId );
		if ( !$revision ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-nonexistent-revision', [ $revisionId ] ), 404 );
		}
		$body['revision'] = $revision;
		// Validate that the user is either an IP or an existing user
		$reportedUser = $body['reportedUser'] ?? '';
		'@phan-var string $reportedUser';
		if ( $this->userNameUtils->isIP( $reportedUser ) ) {
			$reportedUserIdentity = UserIdentityValue::newAnonymous( $reportedUser );
		} else {
			$reportedUserIdentity = $this->userIdentityLookup->getUserIdentityByName( $reportedUser );
		}
		$body['reportedUser'] = $reportedUserIdentity;
		// Truncate the Something else details and Additional details fields.
		if ( array_key_exists( 'details', $body ) && $body['details'] !== null ) {
			$body['details'] = $this->contentLanguage->truncateForVisual(
				$body['details'], CommentStore::COMMENT_CHARACTER_LIMIT
			);
		}
		if ( array_key_exists( 'somethingElseDetails', $body ) && $body['somethingElseDetails'] !== null ) {
			$body['somethingElseDetails'] = $this->contentLanguage->truncateForVisual(
				$body['somethingElseDetails'], CommentStore::COMMENT_CHARACTER_LIMIT
			);
		}

		if ( $body['incidentType'] === IncidentReport::THREAT_TYPE_IMMEDIATE ) {
			if ( !isset( $body['physicalHarmType'] ) ) {
				throw new LocalizedHttpException(
					new MessageValue( 'rest-missing-body-field', [ 'physicalHarmType' ] ),
					429
				);
			}

			if ( isset( $body['behaviorType'] ) ) {
				throw new LocalizedHttpException(
					new MessageValue( 'rest-extraneous-body-fields', [ 'behaviorType' ] ),
					429
				);
			}
		} else {
			if ( !isset( $body['behaviorType'] ) ) {
				throw new LocalizedHttpException(
					new MessageValue( 'rest-missing-body-field', [ 'physicalHarmType' ] ),
					429
				);
			}

			if ( isset( $body['physicalHarmType'] ) ) {
				throw new LocalizedHttpException(
					new MessageValue( 'rest-extraneous-body-fields', [ 'physicalHarmType' ] ),
					429
				);
			}
		}

		return IncidentReport::newFromRestPayload(
			$this->getAuthority()->getUser(),
			$body
		);
	}

	/**
	 * Authorises the incident report. If the authorisation fails, a LocalizedHttpException
	 * is thrown. Otherwise the authorisation succeeded.
	 *
	 * Should be called just before an attempt to record and notify is made as this
	 * will increase the rate limit. Doing this before form validation checks would
	 * mean reports that were not sent would be counted towards the rate limit.
	 *
	 * @param UserIdentity $user The UserIdentity associated with the authority.
	 * @return void
	 * @throws LocalizedHttpException On authorisation failure.
	 */
	private function authorizeIncidentReport( $user ): void {
		$user = $this->userFactory->newFromUserIdentity( $user );
		$status = PermissionStatus::newEmpty();
		if ( !$this->getAuthority()->authorizeAction( 'reportincident', $status ) ) {
			if ( $status->hasMessage( 'actionthrottledtext' ) ) {
				$this->logger->warning(
					'User "{user}" tripped rate limits for "reportincident".',
					[ 'user' => $this->getAuthority()->getUser()->getName() ]
				);
				throw new LocalizedHttpException(
					new MessageValue( 'apierror-ratelimited' ),
					429
				);
			} else {
				if ( $user->isTemp() ) {
					// We'll deny temp users later on in the authorizeAction check below.
					$this->logger->warning(
						'Temporary user "{user}" attempted to perform "reportincident".',
						[ 'user' => $this->getAuthority()->getUser()->getName() ]
					);
				} else {
					$this->logger->warning(
						'User "{user}" without permissions attempted to perform "reportincident".',
						[ 'user' => $this->getAuthority()->getUser()->getName() ]
					);
				}
				throw new LocalizedHttpException(
					new MessageValue( 'apierror-permissiondenied', [ 'reportincident' ] ),
					403
				);
			}
		}
	}

	/**
	 * Submits an incident report given using the
	 * IncidentReport object. Does not perform
	 * any validation checks.
	 *
	 * @param IncidentReport $incidentReport The IncidentReport object generated from the request
	 * @return Response The Response object to be returned by ::run.
	 */
	private function submitIncidentReport( IncidentReport $incidentReport ): Response {
		$status = $this->reportIncidentManager->record( $incidentReport );

		if ( !$status->isGood() ) {
			throw new LocalizedHttpException(
				new MessageValue( $status->getErrors()[0]['message'] ), 400
			);
		}

		// For reports of unacceptable behavior, only create a private record
		// without notifying the emergency team.
		if ( $incidentReport->getIncidentType() === IncidentReport::THREAT_TYPE_UNACCEPTABLE_BEHAVIOR ) {
			return $this->getResponseFactory()->createNoContent();
		}

		// TODO: Once we store the reports in a DB table (T345246), we can notify T&S about the report
		// in a deferred update, so the user doesn't need to wait. For now, this is our
		// only signal that a report was processed, so we need to verify it succeeded.
		$status = $this->reportIncidentManager->notify( $incidentReport );
		if ( !$status->isGood() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'reportincident-unable-to-send' ),
				500
			);
		}

		return $this->getResponseFactory()->createNoContent();
	}

	public function getBodyParamSettings(): array {
		return [
			'reportedUser' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'revisionId' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'incidentType' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => [
					IncidentReport::THREAT_TYPE_UNACCEPTABLE_BEHAVIOR,
					IncidentReport::THREAT_TYPE_IMMEDIATE,
				],
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_ISMULTI => false,
			],
			'physicalHarmType' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => [
					'threats-physical-harm',
					'threats-self-harm',
					'threats-public-harm',
				],
				ParamValidator::PARAM_REQUIRED => false,
			],
			'behaviorType' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => IncidentReport::behaviorTypes(),
				ParamValidator::PARAM_REQUIRED => false,
			],
			'details' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'somethingElseDetails' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'threadId' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		] + $this->getTokenParamDefinition();
	}

}
