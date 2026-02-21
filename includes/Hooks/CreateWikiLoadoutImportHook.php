<?php

namespace Miraheze\CreateWiki\Hooks;

use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Shell\Shell;
use Miraheze\CreateWiki\ConfigNames;

class CreateWikiLoadoutImportHook implements
	CreateWikiAfterCreationWithExtraDataHook,
	CreateWikiCreationExtraFieldsHook {

	public function __construct( private readonly Config $config ) {
	}

	public function onCreateWikiCreationExtraFields( array &$extraFields ): void {
		$extraFields[] = 'loadout';
	}

	public function onCreateWikiAfterCreationWithExtraData( array $extraData, string $dbname ): void {
		if ( empty( $extraData['loadout'] ) ) {
			return;
		}

		$loadouts = $this->config->get( ConfigNames::WikiLoadouts );
		if ( !isset( $loadouts[$extraData['loadout']] ) ) {
			LoggerFactory::getInstance( 'CreateWiki' )->warning(
				"Invalid loadout '{loadout}' specified for wiki {dbname}",
				[
					'loadout' => $extraData['loadout'],
					'dbname' => $dbname
				]
			);
			return;
		}

		$loadoutConfig = $loadouts[$extraData['loadout']];
		if ( !isset( $loadoutConfig['xml'] ) || !$loadoutConfig['xml'] ) {
			LoggerFactory::getInstance( 'CreateWiki' )->error(
				"Loadout '{loadout}' has no XML file configured for wiki {dbname}",
				[
					'loadout' => $extraData['loadout'],
					'dbname' => $dbname
				]
			);
			return;
		}

		$xmlPath = $loadoutConfig['xml'];

		if ( !file_exists( $xmlPath ) || !is_readable( $xmlPath ) ) {
			LoggerFactory::getInstance( 'CreateWiki' )->error(
				"XML dump file {path} not found or not readable",
				[
					'path' => $xmlPath,
					'dbname' => $dbname
				]
			);
			return;
		}

		try {
			$limits = [
				'memory' => 0,
				'filesize' => 0,
				'time' => 0,
				'walltime' => 0
			];
			$result = Shell::makeScriptCommand(
				'importDump',
				[
					'--wiki',
					$dbname,
					$xmlPath,
					'--username-prefix',
					'',
				]
			)->limits( $limits )->execute();

			if ( $result->getExitCode() !== 0 ) {
				$stderr = $result->getStderr();
				LoggerFactory::getInstance( 'CreateWiki' )->error(
					"ImportDump failed for wiki {dbname}: {error}",
					[
						'dbname' => $dbname,
						'error' => $stderr
					]
				);
			}
		} catch ( Exception $e ) {
			LoggerFactory::getInstance( 'CreateWiki' )->error(
				"Exception during importDump for wiki {dbname}: {exception}",
				[
					'dbname' => $dbname,
					'exception' => $e->getMessage()
				]
			);
		}
	}
}
