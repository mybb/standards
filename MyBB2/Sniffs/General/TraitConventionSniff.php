<?php
/**
 * Sniff to ensure that only one trait per 'use' is included
 *
 * @author  MyBB Group
 * @version 2.0.0
 * @package mybb/standards
 * @license http://www.mybb.com/licenses/bsd3 BSD-3
 */

class MyBB2_Sniffs_General_TraitConventionSniff implements PHP_CodeSniffer_Sniff
{

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(
				T_USE
			   );
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The current file being processed.
	 * @param int                  $stackPtr  The position of the current token
	 *                                        in the stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens = $phpcsFile->getTokens();
		
		// Only work on level 1 "use", means including traits
		if ($tokens[$stackPtr]['level' ] != 1) {
			return;
		}

		$nextComma = $phpcsFile->findNext(T_COMMA, $stackPtr);
		$nextSemi = $phpcsFile->findNext(T_SEMICOLON, $stackPtr);

		if ($nextComma !== false && $nextComma < $nextSemi) {
			$error = 'Only one trait should be included per "use"';
			$phpcsFile->addError($error, $stackPtr, 'OnlyOneTraitPerUse');
		}
	}
}
