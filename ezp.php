#!/usr/bin/env php
<?php
/**
 * File containing the ezp.php script.
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

/**
 * This script is the root script to eZ Publish bin/php scripts.
 *
 * Unlike individual scripts, it offers bash completion through the /etc/bash_completion.d/ezp script.
 *
 * Return values:
 * - 0: OK
 * - 1: unknown command
 * - 2: unknown script
 *
 * Arguments:
 * - scripts: returns a space separated list of available scripts
 * -
 */

require 'autoload.php';

$input = new ezcConsoleInput();
try {
    $input->process();
} catch( ezcConsoleOptionException $e )
{
    die( $e->getMessage() );
}

$arguments = $input->getArguments();
if ( count( $arguments ) === 0 )
{
    return 1;
}

switch( $arguments[0] )
{
    case 'scripts':
        echo implode( ' ', getScripts() );
        break;

    case 'args':
        if ( !isset( $arguments[1] ) )
            return 2;
        $script = getScript( $arguments[1] );
        if ( $script == false )
            return 2;
        $arguments = getArguments( $script );
        if ( $arguments == false )
            return 2;
        echo implode( ' ', $arguments );
        break;

    default:
        return 1;
}

/**
 * Returns the list of available scripts, without the ez/ezp prefix and without the .php extension
 * @return array
 */
function getScripts()
{
    $iterator = new GlobIterator( 'bin/php/*.php' );
    foreach( $iterator as $key => $script )
    {
        if ( !$script->isFile() )
            continue;

        // $scriptName = str_replace( '.php', '', $script->getFilename() );
        $scriptName = preg_replace( '#(ezp?)?(.*)\.php#', '$2', $script->getFilename() );
        $scripts[] = $scriptName;
    }
    return $scripts;
}

/**
 * Returns the path to a script based on the stripped name (as returned by getScripts)
 * @return string
 */
function getScript( $script )
{
    $candidatePaths = array( 'bin/php/ez', 'bin/php/ezp', 'bin/php/' );
    foreach( $candidatePaths as $candidatePath )
    {
        $path = "{$candidatePath}{$script}.php";
        if ( file_exists( $path ) )
            return $path;
    }
    return false;
}

/**
 * Returns the arguments list for $script
 * @param string $script (root) relative path to the script
 * @return array List of arguments, dashes included
 */
function getArguments( $script )
{
    $helpText = `php $script --help`;
    $eZScriptPattern = '/^  (?:(-[-_a-z0-9+]),)?(--[-_a-z0-9]+)(?:=VALUE)?/ms';
    $ezcPattern = '/^(?:(-[-_a-z0-9+]) \/ )?(--[-_a-z0-9]+)(?:=VALUE)?/ms';

    if ( !preg_match_all( $eZScriptPattern, $helpText, $options, PREG_SET_ORDER ) )
        if ( !preg_match_all( $ezcPattern, $helpText, $options, PREG_SET_ORDER ) )
            return false;

    $result = array();
    foreach( $options as $option )
    {
        if ( !empty( $option[1] ) )
            $result[] = $option[1];

        if ( !empty( $option[2] ) )
            $result[] = $option[2];
    }

    return $result;
}
?>