<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @link       http://patchwork2.org/
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\CallRerouting;

use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CallRerouting;
use Patchwork\Utils;

const CALL_INTERCEPTION_CODE = '
    $__pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\\\{closure}" : "\\\\{closure}";
    $__pwClass = (__CLASS__ && __FUNCTION__ !== $__pwClosureName) ? __CLASS__ : null;
    if (!empty(\Patchwork\CallRerouting\State::$routes[$__pwClass][__FUNCTION__])) {
        $__pwCalledClass = $__pwClass ? \get_called_class() : null;
        $__pwFrame = \count(\debug_backtrace(0));
        $__pwRefs = %s;
        $__pwRefOffset = 0;
        if (\Patchwork\CallRerouting\dispatch($__pwClass, $__pwCalledClass, __FUNCTION__, $__pwFrame, $__pwResult, \array_merge(\array_slice($__pwRefs, $__pwRefOffset, \func_num_args()), \array_slice(\func_get_args(), \count($__pwRefs))))) {
            return $__pwResult;
        }
    }
    unset($__pwClass, $__pwCalledClass, $__pwResult, $__pwClosureName, $__pwFrame, $__pwRefs, $__pwRefOffset);
';

const CALL_INTERCEPTION_CODE_VOID_TYPED = '
    $__pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\\\{closure}" : "\\\\{closure}";
    $__pwClass = (__CLASS__ && __FUNCTION__ !== $__pwClosureName) ? __CLASS__ : null;
    if (!empty(\Patchwork\CallRerouting\State::$routes[$__pwClass][__FUNCTION__])) {
        $__pwCalledClass = $__pwClass ? \get_called_class() : null;
        $__pwFrame = \count(\debug_backtrace(0));
        $__pwRefs = %s;
        $__pwRefOffset = 0;
        if (\Patchwork\CallRerouting\dispatch($__pwClass, $__pwCalledClass, __FUNCTION__, $__pwFrame, $__pwResult, \array_merge(\array_slice($__pwRefs, $__pwRefOffset, \func_num_args()), \array_slice(\func_get_args(), \count($__pwRefs))))) {
			if ($__pwResult !== null) {
				throw new \Patchwork\Exceptions\NonNullToVoid;
			}
			return;
		}
    }
    unset($__pwClass, $__pwCalledClass, $__pwResult, $__pwClosureName, $__pwFrame, $__pwRefOffset);
';

const CALL_INTERCEPTION_CODE_NEVER_TYPED = '
    $__pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\\\{closure}" : "\\\\{closure}";
    $__pwClass = (__CLASS__ && __FUNCTION__ !== $__pwClosureName) ? __CLASS__ : null;
    if (!empty(\Patchwork\CallRerouting\State::$routes[$__pwClass][__FUNCTION__])) {
        $__pwCalledClass = $__pwClass ? \get_called_class() : null;
        $__pwFrame = \count(\debug_backtrace(0));
        $__pwRefs = %s;
        $__pwRefOffset = 0;
        if (\Patchwork\CallRerouting\dispatch($__pwClass, $__pwCalledClass, __FUNCTION__, $__pwFrame, $__pwResult, \array_merge(\array_slice($__pwRefs, $__pwRefOffset, \func_num_args()), \array_slice(\func_get_args(), \count($__pwRefs))))) {
            throw new \Patchwork\Exceptions\ReturnFromNever;
        }
    }
    unset($__pwClass, $__pwCalledClass, $__pwResult, $__pwClosureName, $__pwFrame, $__pwRefOffset);
';

const QUEUE_DEPLOYMENT_CODE = '\Patchwork\CallRerouting\deployQueue()';

function markPreprocessedFiles()
{
    return Generic\markPreprocessedFiles(CallRerouting\State::$preprocessedFiles);
}

function injectCallInterceptionCode()
{
    return Generic\prependCodeToFunctions(
        Utils\condense(CALL_INTERCEPTION_CODE),
        array(
            'void' => Utils\condense(CALL_INTERCEPTION_CODE_VOID_TYPED),
            'never' => Utils\condense(CALL_INTERCEPTION_CODE_NEVER_TYPED),
        ),
        true
    );
}

function injectQueueDeploymentCode()
{
    return Generic\chain(array(
        Generic\injectFalseExpressionAtBeginnings(QUEUE_DEPLOYMENT_CODE),
        Generic\injectCodeAfterClassDefinitions(QUEUE_DEPLOYMENT_CODE . ';'),
    ));
}
