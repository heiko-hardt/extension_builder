<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Nico de Haen
 *  All rights reserved
 *
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 *
 * This tests takes a extension configuration generated with Version 1.0
 * generates a complete Extension and compares it with the one generated with Version 1
 *
 *
 * @author Nico de Haen
 *
 */
class Tx_ExtensionBuilder_CompatibilityFunctionTest extends Tx_ExtensionBuilder_Tests_BaseTest {

	function setUp() {
		parent::setUp();
	}

	/**
	 * @test
	 */
	public function checkRequirements() {
		$this->assertTrue(class_exists(vfsStream), 'Requirements not fulfilled: vfsStream is needed for file operation tests. Please make sure you are using at least phpunit Version 3.5.6');
	}


	/**
	 * This test creates an extension based on a JSON file, generated
	 * with version 1.0 of the ExtensionBuilder and compares all
	 * generated files with the originally created ones
	 * This test should help, to find compatibility breaking changes
	 *
	 * @test
	 */
	function generateExtensionFromVersion1Configuration() {
		$this->configurationManager = $this->getMock($this->buildAccessibleProxy('Tx_ExtensionBuilder_Configuration_ConfigurationManager'), array('dummy'));
		$this->extensionSchemaBuilder = $this->objectManager->get('Tx_ExtensionBuilder_Service_ExtensionSchemaBuilder');

		$testExtensionDir = PATH_typo3conf . 'ext/extension_builder/Tests/Examples/TestExtensions/test_extension_v1/';
		$jsonFile = $testExtensionDir . Tx_ExtensionBuilder_Configuration_ConfigurationManager::EXTENSION_BUILDER_SETTINGS_FILE;

		if (file_exists($jsonFile)) {
			// compatibility adaptions for configurations from older versions
			$extensionConfigurationJSON = json_decode(file_get_contents($jsonFile), TRUE);
			$extensionConfigurationJSON = $this->configurationManager->fixExtensionBuilderJSON($extensionConfigurationJSON, FALSE);

		} else {
			$this->fail('JSON file not found');
		}

		$this->extension = $this->extensionSchemaBuilder->build($extensionConfigurationJSON);
		$this->codeGenerator->setSettings(
			array(
				 'codeTemplateRootPath' => PATH_typo3conf . 'ext/extension_builder/Resources/Private/CodeTemplates/Extbase/',
				 'extConf' => array(
					 'enableRoundtrip' => '0'
				 )
			)
		);
		$newExtensionDir = vfsStream::url('testDir') . '/';

		$this->extension->setExtensionDir($newExtensionDir . 'test_extension/');

		$this->codeGenerator->build($this->extension);

		$referenceFiles = t3lib_div::getAllFilesAndFoldersInPath(array(), $testExtensionDir);

		foreach ($referenceFiles as $referenceFile) {
			$createdFile = str_replace($testExtensionDir, $this->extension->getExtensionDir(), $referenceFile);
			if (!in_array(basename($createdFile), array('ExtensionBuilder.json'))) { // json file is generated by controller
				$referenceFileContent = str_replace(
					array('2011-08-11', '###YEAR###'),
					array(date('Y-m-d'), date('Y')),
					file_get_contents($referenceFile)
				);
				//t3lib_div::writeFile(PATH_site.'fileadmin/'.basename($createdFile), file_get_contents($createdFile));
				$this->assertFileExists($createdFile, 'File ' . $createdFile . ' was not created!');
				$this->assertEquals(
					t3lib_div::trimExplode("\n",$referenceFileContent, TRUE),
					t3lib_div::trimExplode("\n",file_get_contents($createdFile), TRUE),
					'File ' . $createdFile . ' was not equal to original file.'
				);
			}
		}

	}


}

?>
