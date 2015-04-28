<?php

class MyBB2_Sniffs_Naming_TraitConventionSniff implements PHP_CodeSniffer_Sniff
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
				T_TRAIT
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

		$endsWithInterface = substr($name, -5) == 'Trait';
		$isClass = $tokens[$stackPtr]['code'] === T_CLASS;

		if ($isClass && $endsWithInterface) {
			$error = '%s name must not end with "Trait"';
			$phpcsFile->addError($error, $stackPtr, 'EndNotWithTrait', $errorData);
		}

		if (!$isClass && !$endsWithInterface) {
			$error = '%s name must end with "Trait"';
			$phpcsFile->addError($error, $stackPtr, 'EndWithTrait', $errorData);
		}
	}
}
