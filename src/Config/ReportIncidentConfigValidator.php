<?php
namespace MediaWiki\Extension\ReportIncident\Config;

use Iterator;
use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\Schema\SchemaBuilder;
use MediaWiki\Extension\CommunityConfiguration\Validation\IValidator;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidationStatus;
use MediaWiki\Extension\CommunityConfiguration\Validation\ValidatorFactory;
use MediaWiki\Page\PageLookup;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\TitleParser;

/**
 * Validator class used by CommunityConfiguration for IRS local links.
 */
class ReportIncidentConfigValidator implements IValidator {
	private IValidator $jsonSchemaValidator;
	private TitleParser $titleParser;
	private PageLookup $pageLookup;
	private IContextSource $context;

	public function __construct(
		IValidator $jsonSchemaValidator,
		TitleParser $titleParser,
		PageLookup $pageLookup,
		IContextSource $context
	) {
		$this->jsonSchemaValidator = $jsonSchemaValidator;
		$this->titleParser = $titleParser;
		$this->pageLookup = $pageLookup;
		$this->context = $context;
	}

	public static function factory(
		TitleParser $titleParser,
		PageLookup $pageLookup,
		ValidatorFactory $validatorFactory,
		string $jsonSchema,
		?IContextSource $context = null
	): self {
		$jsonSchemaValidator = $validatorFactory->newValidator(
			'ReportIncident',
			'jsonschema',
			[ $jsonSchema ],
		);

		$context ??= RequestContext::getMain();

		return new self(
			$jsonSchemaValidator,
			$titleParser,
			$pageLookup,
			$context
		);
	}

	/** @inheritDoc */
	public function validateStrictly( $config ): ValidationStatus {
		$status = $this->jsonSchemaValidator->validateStrictly( $config );
		if ( !$status->isOK() ) {
			return $status;
		}

		return $this->validateConfig( $config );
	}

	/** @inheritDoc */
	public function validatePermissively( $config ): ValidationStatus {
		$status = $this->jsonSchemaValidator->validatePermissively( $config );
		if ( !$status->isOK() ) {
			return $status;
		}

		return $this->validateConfig( $config );
	}

	/**
	 * Validate values supplied by community configuration.
	 * @param \stdClass $config
	 * @return ValidationStatus
	 */
	private function validateConfig( $config ): ValidationStatus {
		$status = new ValidationStatus();

		$titleProps = [
			'ReportIncidentDisputeResolutionPage',
			'ReportIncidentLocalIncidentReportPage',
			'ReportIncidentCommunityQuestionsPage',
		];

		foreach ( $titleProps as $key ) {
			try {
				// Treat an empty string value as if the value was not set.
				$value = $config->$key ?? '';
				if ( $value === '' ) {
					continue;
				}

				$title = $this->titleParser->parseTitle( $value );
				if ( $title->isExternal() ) {
					$status->addFatal(
						$key,
						"/$key",
						$this->context->msg( 'communityconfiguration-reportincident-invalid-title' )->text()
					);
					continue;
				}

				if ( $title->getNamespace() <= NS_SPECIAL ) {
					$status->addFatal(
						$key,
						"/$key",
						$this->context->msg( 'communityconfiguration-reportincident-invalid-title' )->text()
					);
					continue;
				}

				$page = $this->pageLookup->getPageForLink( $title );
				if ( !$page->exists() ) {
					$status->addFatal(
						$key,
						"/$key",
						$this->context->msg( 'communityconfiguration-reportincident-invalid-title' )->text()
					);
				}
			} catch ( MalformedTitleException $e ) {
				$status->addFatal(
					$key,
					"/$key",
					$this->context->msg( $e->getMessageObject() )->text()
				);
			}
		}

		return $status;
	}

	/** @inheritDoc */
	public function areSchemasSupported(): bool {
		return $this->jsonSchemaValidator->areSchemasSupported();
	}

	/** @inheritDoc */
	public function getSchemaBuilder(): SchemaBuilder {
		return $this->jsonSchemaValidator->getSchemaBuilder();
	}

	/** @inheritDoc */
	public function getSchemaIterator(): Iterator {
		return $this->jsonSchemaValidator->getSchemaIterator();
	}

	/** @inheritDoc */
	public function getSchemaVersion(): ?string {
		return $this->jsonSchemaValidator->getSchemaVersion();
	}
}
