<?php
/**
 * Sniff to ensure that abstract classes (and only them) are prefixed with 'Abstract'
 *
 * @author  MyBB Group
 * @version 2.0.0
 * @package mybb/standards
 * @license http://www.mybb.com/licenses/bsd3 BSD-3
 */

class MyBB2_Sniffs_Naming_AbstractClassConventionSniff implements PHP_CodeSniffer_Sniff
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
		$classProperties = $phpcsFile->getClassProperties($stackPtr);

		$startsWithAbstract = substr($name, 0, 8) == 'Abstract';

		if ($classProperties['is_abstract'] && !$startsWithAbstract) {
			$error = '%s name must begin with "Abstract"';
			$phpcsFile->addError($error, $stackPtr, 'StartWithAbstract', $errorData);
		}

		if (!$classProperties['is_abstract'] && $startsWithAbstract) {
			$error = '%s name must not begin with "Abstract"';
			$phpcsFile->addError($error, $stackPtr, 'StartNotWithAbstract', $errorData);
		}
	}
}
