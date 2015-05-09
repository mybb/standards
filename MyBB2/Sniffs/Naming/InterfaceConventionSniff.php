<?php
/**
 * Sniff to ensure that Interfaces (and only them) are suffixed with 'Interface'
 *
 * @author  MyBB Group
 * @version 2.0.0
 * @package mybb/standards
 * @license http://www.mybb.com/licenses/bsd3 BSD-3
 */

class MyBB2_Sniffs_Naming_InterfaceConventionSniff implements PHP_CodeSniffer_Sniff
{

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(
				T_CLASS,
				T_INTERFACE
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
		$className = $phpcsFile->findNext(T_STRING, $stackPtr);
		$name      = trim($tokens[$className]['content']);
		$errorData = array(ucfirst($tokens[$stackPtr]['content']));

		$endsWithInterface = substr($name, -9) == 'Interface';
		$isClass = $tokens[$stackPtr]['code'] === T_CLASS;

		if ($isClass && $endsWithInterface) {
			$error = '%s name must not end with "Interface"';
			$phpcsFile->addError($error, $stackPtr, 'EndNotWithInterface', $errorData);
		}

		if (!$isClass && !$endsWithInterface) {
			$error = '%s name must end with "Interface"';
			$phpcsFile->addError($error, $stackPtr, 'EndWithInterface', $errorData);
		}
	}
}
