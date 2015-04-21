<?php

class MyBB2_Sniffs_Commenting_FunctionCommentSniff extends PEAR_Sniffs_Commenting_FunctionCommentSniff
{
	private $hasReturn;
	
	
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

		$nextReturn = $phpcsFile->findNext(T_RETURN, $stackPtr);
		$nextFunction = $phpcsFile->findNext(T_FUNCTION, $stackPtr+1);

		// Ignore functions without return type
		$this->hasReturn = !($nextReturn === false || $nextReturn > $nextFunction);

		$parameters = $phpcsFile->getMethodParameters($stackPtr);

		$find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
		$find[] = T_WHITESPACE;
		$commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

		if ($tokens[$commentEnd]['code'] === T_COMMENT) {
			// Inline comments might just be closing comments for
			// control structures or functions instead of function comments
			// using the wrong comment type. If there is other code on the line,
			// assume they relate to that code.
			$prev = $phpcsFile->findPrevious($find, ($commentEnd - 1), null, true);
			if ($prev !== false && $tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
				$commentEnd = $prev;
			}
		}
		
		// we only require the doc for methods with either a return value or parameters
		// However if we don't have any of it but a doc block is created we still want to check it
		if (!$this->hasReturn && empty($parameters)) {
			if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
				&& $tokens[$commentEnd]['code'] !== T_COMMENT
			) {
				return;
			}
		}

		$commentStart = 0;
		if (isset($tokens[$commentEnd]['comment_opener'])) {
			$commentStart = $tokens[$commentEnd]['comment_opener'];
		}

		if ($this->isValidInheritationComment($phpcsFile, $commentStart, $commentEnd)) {
			return;
		}
		
		parent::process($phpcsFile, $stackPtr);

		if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
			return;
		}
		
		$this->processNewLine($phpcsFile, $commentStart);
	}

	public function isValidInheritationComment(PHP_CodeSniffer_File $phpcsFile, $commentStart, $commentEnd)
	{
		$tokens = $phpcsFile->getTokens();

		$hadInherit = $hadOther = false;
		$validInheritElements = array(
			T_DOC_COMMENT_OPEN_TAG,
			T_DOC_COMMENT_WHITESPACE,
			T_DOC_COMMENT_STAR,
			T_DOC_COMMENT_TAG,
			T_DOC_COMMENT_CLOSE_TAG,
		);

		for ($i=$commentStart; $i<=$commentEnd; $i++) {
			$type = $tokens[$i]['code'];

			// Ignore valid elements
			if (in_array($type, $validInheritElements)) {
				continue;
			}

			if ($type === T_DOC_COMMENT_STRING) {
				$string = trim($tokens[$i]['content']);
				
				// Ignore emtpy strings
				if (empty($string)) {
					continue;
				}
				
				// If we have our inheritdoc element and hadn't it before we'll save that
				if (strtolower($string) == '{@inheritdoc}') {
					if ($hadInherit) {
						$error = '"{@inheritdoc}" can only be added once';
						$phpcsFile->addError($error, $i, 'TwoInheritations');
						return false;
					}
					$hadInherit = true;
				}
			}

			// If we found inhert before an have something else we can throw an error_get_last
			if ($hadInherit && $hadOther) {
				$error = '"{@inheritdoc}" needs to be the only element';
				$phpcsFile->addError($error, $i, 'OnlyOneInheritation');
				return false;
			}

			$hadOther = true;
		}

		// Valid Inherit? Skip the rest. Other errors are thrown above
		if ($hadInherit) {
			return true;
		}
		
		return false;
	}
	
	private function processNewLine(PHP_CodeSniffer_File $phpcsFile, $commentStart)
	{
		$tokens = $phpcsFile->getTokens();

		$prevTag = array();
		$firstTagLine = array('line' => -1);
		foreach ($tokens[$commentStart]['comment_tags'] as $tagIndex) {
			$tag = $tokens[$tagIndex];

			if (!empty($prevTag)) {
				$lineDiff = $tag['line']-$prevTag['line'];

				// New tag type? And they're not seperated by one empty line? That's our error
				if ($prevTag['type'] != $tag['content']) {
					$lineDiffShouldBe = 2;
					
					if ($prevTag['type'] == '@param') {
						$nextIndex = $phpcsFile->findNext(array(T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING), $prevTag['index']+1);

						while ($tokens[$nextIndex]['code'] != T_DOC_COMMENT_TAG) {
							$lineDiffShouldBe = $lineDiffShouldBe + $tokens[$nextIndex]['line'] - $prevTag['line'];
						
							$nextIndex = $phpcsFile->findNext(array(T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING), $nextIndex+1);
						}
					}

					if ($lineDiff != $lineDiffShouldBe) {
						$error = 'There should be exactly one new line between different tag types';
						$phpcsFile->addError($error, $tagIndex, 'MissingNewLine');
					}
				}
			}

			if ($firstTagLine['line'] == -1 || $tag['line'] < $firstTagLine['line']) {
				$firstTagLine = array(
					'line' => $tag['line'],
					'index' => $tagIndex
				);
			}
			
			$prevTag = array(
				'type' => $tag['content'],
				'line' => $tag['line'],
				'index' => $tagIndex
			);
		}
		
		// No tag? Only comment then, nothing to do
		if ($firstTagLine['line'] == -1) {
			return;
		}
		
		$internalFirstLine = $firstTagLine['line'] - $tokens[$commentStart]['line'];
		
		// The first tag is on the first comment line so we don't need to check other things
		if ($internalFirstLine == 1) {
			return;
		}
		
		$previousStringIndex = $phpcsFile->findPrevious(T_DOC_COMMENT_STRING, $firstTagLine['index']);

		// The comment is somewhere but not two lines above the firsttag
		if ($tokens[$previousStringIndex]['line'] != $firstTagLine['line']-2) {
			$error = 'There should be exactly one new line between different tag types';
			$phpcsFile->addError($error, $tagIndex, 'MissingNewLine');
		}
	}
	
	/**
	 * Process the return comment of this function comment.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
	 * @param int                  $stackPtr     The position of the current token
	 *                                           in the stack passed in $tokens.
	 * @param int                  $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processReturn(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
	{
		// Ignore functions without return type
		if (!$this->hasReturn) {
			return;
		}
		
		parent::processReturn($phpcsFile, $stackPtr, $commentStart);
	}

	/**
	 * Process the function parameter comments.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
	 * @param int                  $stackPtr     The position of the current token
	 *                                           in the stack passed in $tokens.
	 * @param int                  $commentStart The position in the stack where the comment started.
	 *
	 * @return void
	 */
	protected function processParams(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
	{
		$tokens = $phpcsFile->getTokens();
		$params  = array();
		$maxType = 0;
		$maxVar  = 0;
		foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
			if ($tokens[$tag]['content'] !== '@param') {
				continue;
			}
			$type      = '';
			$typeSpace = 0;
			$var       = '';
			$varSpace  = 0;
			$comment   = '';
			if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
				$matches = array();
				preg_match('/([^$&]+)(?:((?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[($tag + 2)]['content'], $matches);
				$typeLen   = strlen($matches[1]);
				$type      = trim($matches[1]);
				$typeSpace = ($typeLen - strlen($type));
				$typeLen   = strlen($type);
				if ($typeLen > $maxType) {
					$maxType = $typeLen;
				}
				if (isset($matches[2]) === true) {
					$var    = $matches[2];
					$varLen = strlen($var);
					if ($varLen > $maxVar) {
						$maxVar = $varLen;
					}
					if (isset($matches[4]) === true) {
						$varSpace = strlen($matches[3]);
						$comment  = $matches[4];
						// Any strings until the next tag belong to this comment.
						if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
							$end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
						} else {
							$end = $tokens[$commentStart]['comment_closer'];
						}
						for ($i = ($tag + 3); $i < $end; $i++) {
							if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
								$comment .= ' '.$tokens[$i]['content'];
							}
						}
					}
				} else {
					$error = 'Missing parameter name';
					$phpcsFile->addError($error, $tag, 'MissingParamName');
				}//end if
			} else {
				$error = 'Missing parameter type';
				$phpcsFile->addError($error, $tag, 'MissingParamType');
			}//end if
			$params[] = array(
						 'tag'        => $tag,
						 'type'       => $type,
						 'var'        => $var,
						 'comment'    => $comment,
						 'type_space' => $typeSpace,
						 'var_space'  => $varSpace,
						);
		}//end foreach
		$realParams  = $phpcsFile->getMethodParameters($stackPtr);
		$foundParams = array();
		foreach ($params as $pos => $param) {
			if ($param['var'] === '') {
				continue;
			}
			$foundParams[] = $param['var'];
			// Check number of spaces after the type.
			$spaces = ($maxType - strlen($param['type']) + 1);
			if ($param['type_space'] !== $spaces) {
				$error = 'Expected %s spaces after parameter type; %s found';
				$data  = array(
						  $spaces,
						  $param['type_space'],
						 );
				$fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamType', $data);
				if ($fix === true) {
					$content  = $param['type'];
					$content .= str_repeat(' ', $spaces);
					$content .= $param['var'];
					$content .= str_repeat(' ', $param['var_space']);
					$content .= $param['comment'];
					$phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
				}
			}
			// Make sure the param name is correct.
			if (isset($realParams[$pos]) === true) {
				$realName = $realParams[$pos]['name'];
				if ($realName !== $param['var']) {
					$code = 'ParamNameNoMatch';
					$data = array(
							 $param['var'],
							 $realName,
							);
					$error = 'Doc comment for parameter %s does not match ';
					if (strtolower($param['var']) === strtolower($realName)) {
						$error .= 'case of ';
						$code   = 'ParamNameNoCaseMatch';
					}
					$error .= 'actual variable name %s';
					$phpcsFile->addError($error, $param['tag'], $code, $data);
				}
			} elseif (substr($param['var'], -4) !== ',...') {
				// We must have an extra parameter comment.
				$error = 'Superfluous parameter comment';
				$phpcsFile->addError($error, $param['tag'], 'ExtraParamComment');
			}//end if
			if ($param['comment'] === '') {
				continue;
			}
			// Check number of spaces after the var name.
			$spaces = ($maxVar - strlen($param['var']) + 1);
			if ($param['var_space'] !== $spaces) {
				$error = 'Expected %s spaces after parameter name; %s found';
				$data  = array(
						  $spaces,
						  $param['var_space'],
						 );
				$fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamName', $data);
				if ($fix === true) {
					$content  = $param['type'];
					$content .= str_repeat(' ', $param['type_space']);
					$content .= $param['var'];
					$content .= str_repeat(' ', $spaces);
					$content .= $param['comment'];
					$phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
				}
			}
		}//end foreach
		$realNames = array();
		foreach ($realParams as $realParam) {
			$realNames[] = $realParam['name'];
		}
		// Report missing comments.
		$diff = array_diff($realNames, $foundParams);
		foreach ($diff as $neededParam) {
			$error = 'Doc comment for parameter "%s" missing';
			$data  = array($neededParam);
			$phpcsFile->addError($error, $commentStart, 'MissingParamTag', $data);
		}
	}
}
