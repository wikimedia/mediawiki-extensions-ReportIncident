<?php

namespace MediaWiki\Extension\ReportIncident\Api\Rest\Handler;

use Config;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Rest\Validator\UnsupportedContentTypeBodyValidator;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserIdentityValue;
use MediaWiki\User\UserNameUtils;
use Psr\Log\LoggerInterface;
use TypeError;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * REST handler for /reportincident/v0/report
 */
class ReportHandler extends SimpleHandler {

	private Config $config;
	private ReportIncidentManager $reportIncidentManager;
	private RevisionStore $revisionStore;
	private UserNameUtils $userNameUtils;
	private UserIdentityLookup $userIdentityLookup;
	private LoggerInterface $logger;
	private UserFactory $userFactory;

	/**
	 * @param Config $config
	 * @param RevisionStore $revisionStore
	 * @param UserNameUtils $userNameUtils
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param ReportIncidentManager $reportIncidentManager
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		Config $config,
		RevisionStore $revisionStore,
		UserNameUtils $userNameUtils,
		UserIdentityLookup $userIdentityLookup,
		ReportIncidentManager $reportIncidentManager,
		UserFactory $userFactory
	) {
		$this->config = $config;
		$this->reportIncidentManager = $reportIncidentManager;
		$this->revisionStore = $revisionStore;
		$this->userNameUtils = $userNameUtils;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->logger = LoggerFactory::getInstance( 'ReportIncident' );
		$this->userFactory = $userFactory;
	}

	public function run() {
		if ( !$this->config->get( 'ReportIncidentApiEnabled' ) ) {
			// Pretend the route doesn't exist if the feature flag is off.
			throw new LocalizedHttpException(
				new MessageValue( 'rest-no-match' ), 404
			);
		}
		$user = $this->getAuthority()->getUser();
		if ( !$user->isRegistered() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-permission-denied-anon' ), 401
			);
		}

		$user = $this->userFactory->newFromUserIdentity( $user );

		if ( $user->getEditCount() === 0 ) {
			$this->logger->warning(
				'User "{user}" with zero edits attempted to perform "reportincident".',
				[ 'user' => $this->getAuthority()->getUser()->getName() ]
			);
			throw new LocalizedHttpException(
				new MessageValue( 'apierror-permissiondenied', [ 'reportincident' ] ),
				403
			);
		}

		$isDeveloperMode = $this->config->get( 'ReportIncidentDeveloperMode' );
		if ( !$isDeveloperMode && !$user->isEmailConfirmed() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'reportincident-confirmedemail-required' ), 403
			);
		}

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
		$body = $this->getValidatedBody();
		// $body should be an array, but can be null when validation
		// failed and/or when the content type was form data.
		if ( !is_array( $body ) ) {
			// Taken from Validator::validateBody
			[ $contentType ] = explode( ';', $this->getRequest()->getHeaderLine( 'Content-Type' ), 2 );
			$contentType = strtolower( trim( $contentType ) );
			if ( $contentType !== 'application/json' ) {
				// Same exception as used in UnsupportedContentTypeBodyValidator
				throw new LocalizedHttpException(
					new MessageValue( 'rest-unsupported-content-type', [ $contentType ] ),
					415
				);
			} else {
				// Should be caught by JsonBodyValidator::validateBody, but if this
				// point is reached a non-array still indicates a problem with the
				// data submitted by the client and thus a 400 error is appropriate.
				throw new LocalizedHttpException( new MessageValue( 'rest-bad-json-body' ), 400 );
			}
		}
		$revisionId = $body['revisionId'];
		$revision = $this->revisionStore->getRevisionById( $revisionId );
		if ( !$revision ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-nonexistent-revision', [ $revisionId ] ), 404 );
		}
		$body['revision'] = $revision;
		// Validate that the user is either an IP or an existing user
		/** @var string $reportedUser */
		$reportedUser = $body['reportedUser'];
		'@phan-var string $reportedUser';
		if ( $this->userNameUtils->isIP( $reportedUser ) ) {
			$reportedUserIdentity = UserIdentityValue::newAnonymous( $reportedUser );
		} else {
			$reportedUserIdentity = $this->userIdentityLookup->getUserIdentityByName( $reportedUser );
			if ( !$reportedUserIdentity || !$reportedUserIdentity->isRegistered() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'rest-nonexistent-user', [ $reportedUser ] ), 404
				);
			}
		}
		$body['reportedUser'] = $reportedUserIdentity;
		try {
			$incidentReport = IncidentReport::newFromRestPayload(
				$this->getAuthority()->getUser(),
				$body
			);
		} catch ( TypeError $typeError ) {
			// Should be caught by JsonBodyValidator::validateBody, but that doesn't validate
			// parameters yet (T305973).
			LoggerFactory::getInstance( 'ReportIncident' )->error( 'Invalid type specified: {message}', [
				'message' => $typeError->getMessage()
			] );
			throw new LocalizedHttpException( new MessageValue( 'rest-bad-json-body' ), 400 );
		}
		$status = $this->reportIncidentManager->record( $incidentReport );
		if ( $status->isGood() ) {
			// TODO: If/when we store the reports in a DB table, we can move sending the email
			// into a deferred update, so the user doesn't need to wait. For now, this is our
			// only signal that a report was processed, so check the status of the sendEmail
			// method
			$status = $this->reportIncidentManager->notify( $incidentReport );
			if ( !$status->isGood() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'reportincident-unable-to-send' )
				);
			}
			return $this->getResponseFactory()->createNoContent();
		} else {
			throw new LocalizedHttpException(
				new MessageValue( $status->getErrors()[0]['message'] ), 400
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType !== 'application/json' ) {
			return new UnsupportedContentTypeBodyValidator( $contentType );
		}
		// FIXME: JsonBodyValidator doesn't actually validate params
		// yet, see T305973
		return new JsonBodyValidator( self::getBodyParamSettings() );
	}

	public function getBodyParamSettings(): array {
		return [
			'reportedUser' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'revisionId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'behaviors' => [
				ParamValidator::PARAM_TYPE => 'array',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'details' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'somethingElseDetails' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

}
