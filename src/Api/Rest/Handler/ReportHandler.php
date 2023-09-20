<?php

namespace MediaWiki\Extension\ReportIncident\Api\Rest\Handler;

use Config;
use MediaWiki\Extension\ReportIncident\IncidentReport;
use MediaWiki\Extension\ReportIncident\Services\ReportIncidentManager;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Rest\Validator\UnsupportedContentTypeBodyValidator;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\User\UserFactory;
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
	private UserFactory $userFactory;

	/**
	 * @param Config $config
	 * @param RevisionStore $revisionStore
	 * @param UserFactory $userFactory
	 * @param ReportIncidentManager $reportIncidentManager
	 */
	public function __construct(
		Config $config,
		RevisionStore $revisionStore,
		UserFactory $userFactory,
		ReportIncidentManager $reportIncidentManager
	) {
		$this->config = $config;
		$this->reportIncidentManager = $reportIncidentManager;
		$this->revisionStore = $revisionStore;
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
		$body = $this->getValidatedBody();
		$revisionId = $body['revisionId'];
		$revision = $this->revisionStore->getRevisionById( $revisionId );
		if ( !$revision ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-nonexistent-revision', [ $revisionId ] ), 404 );
		}
		$body['revision'] = $revision;
		$reportedUser = $this->userFactory->newFromId( (int)$body['reportedUserId'] );
		$body['reportedUser'] = $reportedUser;
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

	public function needsWriteAccess(): bool {
		return true;
	}

	public function getBodyParamSettings(): array {
		return [
			'reportedUserId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'revisionId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'link' => [
				ParamValidator::PARAM_TYPE => 'string',
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
		];
	}

}