<?php

class MyBB2_Sniffs_Commenting_VariableCommentSniff extends Squiz_Sniffs_Commenting_VariableCommentSniff
{
	public function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
	{
		$tokens       = $phpcsFile->getTokens();
		$commentToken = array(
						 T_DOC_COMMENT_CLOSE_TAG,
						);
		$commentEnd = $phpcsFile->findPrevious($commentToken, $stackPtr);
		
		if ($commentEnd !== false) {
			$commentStart = $tokens[$commentEnd]['comment_opener'];

			if ($tokens[$commentStart]['line'] == $tokens[$commentEnd]['line']) {
				$error = 'The variable doc block shouldn\'t be on one line';
				$phpcsFile->addError($error, $commentStart, 'OneLiner');
			}
		}
		
		parent::processMemberVar($phpcsFile, $stackPtr);
	}
}
