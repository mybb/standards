<?php
/**
 * Sniff to check class whether class variables have a doc block. One liners aren't allowed
 *
 * @author  MyBB Group
 * @version 2.0.0
 * @package mybb/standards
 * @license http://www.mybb.com/licenses/bsd3 BSD-3
 */

class MyBB2_Sniffs_Commenting_VariableCommentSniff extends Squiz_Sniffs_Commenting_VariableCommentSniff
{
	/**
	 * @param PHP_CodeSniffer_File $phpcsFile
	 * @param int                  $stackPtr
	 */
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
