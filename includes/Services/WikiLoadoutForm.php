<?php

namespace Miraheze\CreateWiki\Services;

use MediaWiki\Config\ServiceOptions;
use Miraheze\CreateWiki\ConfigNames;

class WikiLoadoutForm {

	public const CONSTRUCTOR_OPTIONS = [
		ConfigNames::EnableLoadoutSelector,
		ConfigNames::WikiLoadouts,
	];

	private readonly array $loadoutOptions;

	public function __construct( ServiceOptions $options ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );

		$this->loadoutOptions = $this->buildLoadoutOptions(
			$options->get( ConfigNames::EnableLoadoutSelector ),
			$options->get( ConfigNames::WikiLoadouts )
		);
	}

	public function isEnabled(): bool {
		return !empty( $this->loadoutOptions );
	}

	public function getFormDescriptor(): array {
		return [
			'type' => 'select',
			'label-message' => 'createwiki-label-loadout',
			'help-message' => 'createwiki-help-loadout',
			'options' => $this->loadoutOptions,
			'default' => '',
		];
	}

	private function buildLoadoutOptions( bool $enableLoadoutSelector, ?array $loadouts ): array {
		if ( !$enableLoadoutSelector || !$loadouts ) {
			return [];
		}
		$options = [ wfMessage( 'createwiki-label-loadout-none' )->text() => '' ];
		foreach ( $loadouts as $loadoutKey => $loadout ) {
			$options[ wfMessage( "createwiki-label-loadout-$loadoutKey" )->text() ] = $loadoutKey;
		}
		return $options;
	}
}
