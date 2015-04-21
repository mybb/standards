<?php

class MyBB2_Sniffs_Properties_OnlyPrivateAndProtectedPropertiesSniff implements PHP_CodeSniffer_Sniff
{

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register()
	{
		return array(
				T_VARIABLE
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
		
		// That's not a class variable so ignore it
		if ($tokens[$stackPtr]['level'] != 1) {
			return;
		}
		
		$visibility = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$scopeModifiers, $stackPtr);
		// Not the same line, not our modifier. PSR-2 will throw an error in that case so ignore it
		if ($tokens[$visibility]['line'] !== $tokens[$stackPtr]['line']) {
			return;
		}

		$function = $phpcsFile->findPrevious(T_FUNCTION, $stackPtr);
		// We don't want to react on functions (public function xy ($var))
		if ($function > $visibility) {
			return;
		}
		
		$modifier = $tokens[$visibility]['code'];
		
		$data = array(trim($tokens[$stackPtr]['content']));

		if ($modifier != T_PRIVATE && $modifier != T_PROTECTED) {
			$error = '%s must be "private" or "protected"';
			$phpcsFile->addError($error, $stackPtr, 'NotPrivateOrProtected', $data);
		}
	}
}
