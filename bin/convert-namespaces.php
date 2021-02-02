#!/usr/bin/env php
<?php

/**
 * Super-simple script to convert a existing project to use namespaces
 *
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2012 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://opensource.org/licenses/BSD-3-Clause
 */
class namespaceRefactor
{

    /**
     * The found files
     *
     * @var array
     */
    protected $_aFiles = array();

    /**
     * The found classes
     *
     * @var array
     */
    protected $_aClasses = array();

    /**
     * Write the results to the file or just dump the changes
     *
     * @var bool
     */
    protected $_bWrite = false;

    /**
     * The directory where we're looking for files
     *
     * @var string
     */
    protected $_sDirectory = '';

    /**
     * An optional prefix
     *
     * @var string
     */
    protected $_sPrefix;

    /**
     * Require and include will be removed
     *
     * @var bool
     */
    protected $_bAutoloaded = false;

    /**
     * Create
     *
     * @param array $aOpts
     */
    public function __construct(array $aOpts)
    {
        $this->_sDirectory = $aOpts['d'];
        $this->_sPrefix = $aOpts['p'];
        if ($aOpts['w'] === 'true') {
            $this->_bWrite = true;
        }
        $this->_bAutoloaded = isset($aOpts['a']);
    }

    /**
     * Get all php-files from the given directory
     *
     * @return namespaceRefactor
     */
    public function getFiles()
    {
        $oIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_sDirectory));
        $aSuffixes = array(
            'php'
        );

        $sRegex = '/^.+\.(' . implode('|', $aSuffixes) . ')$/i';
        $oFiles = new RegexIterator($oIterator, $sRegex, RecursiveRegexIterator::GET_MATCH);
        $this->_aFiles = array();
        foreach ($oFiles as $aFile) {
            $this->_aFiles[] = $aFile[0];
        }

        return $this;
    }

    /**
     * Parse all files and find class-declarations
     * - If a file does contain a namespace declaration, the class-declaration should not be converted
     *
     * @return namespaceRefactor
     */
    public function readClasses()
    {
        $aFiles = array();
        foreach ($this->_aFiles as $sFile) {
            $aFiles[$sFile] = array();
            $aTokens = token_get_all(file_get_contents($sFile));

            $aFound = array();
            $bLocked = $bClassFound = false;
            foreach ($aTokens as $aToken) {
                $sType = is_array($aToken) ? $aToken[0] : null;
                $sData = (string)(is_array($aToken) ? $aToken[1] : $aToken);

                if ($bLocked === false and $bClassFound === true and $sType === T_STRING) {
                    $this->_aClasses[$sData] = $sData;
                    $aFound[] = $sData;
                    $aFiles[$sFile] = $sData;
                    $bClassFound = false;
                }

                if ($sType === T_CLASS or $sType === T_INTERFACE) {
                    $bClassFound = true;
                }

                // ensure, that classes which are already namespaced are not replaced again
                if ($sType === T_NAMESPACE) {
                    $bLocked = true;
                    foreach ($aFound as $sFoundClass) {
                        if (empty($this->_aClasses[$sFoundClass]) !== true) {
                            unset($this->_aClasses[$sFoundClass]);
                        }
                    }
                }
            }
        }

        $this->_aFiles = $aFiles;
        return $this;
    }

    /**
     * Create the new namespace class for all found classes
     *
     * @return namespaceRefactor
     */
    public function getNamespaces()
    {
        $aClasses = array();
        foreach ($this->_aClasses as $sClass) {
            $aClass = explode('_', $sClass);
            if (count($aClass) > 1 or empty($this->_sPrefix) !== true) {
                $sNewClass = array_pop($aClass);
                $sNamespace = (count($aClass) > 1) ? sprintf('\%s', implode('\\', $aClass)) : '';
                $aClasses[$sClass] = array(
                    'class' => $sNewClass,
                    'namespace' => $sNamespace
                );

                if (empty($this->_sPrefix) !== true) {
                    $iLenght = strlen($this->_sPrefix);
                    if (substr($aClasses[$sClass]['namespace'], 1, $iLenght) !== $this->_sPrefix) {
                        $aClasses[$sClass]['namespace'] = sprintf('\%s%s', $this->_sPrefix,
                            $aClasses[$sClass]['namespace']);
                    }
                }
            }
        }

        $this->_aClasses = $aClasses;
        return $this;
    }

    /**
     * Replace the declarations
     *
     * @return void
     */
    public function replace()
    {
        foreach ($this->_aFiles as $sFile => $sClass) {
            $sContent = file_get_contents($sFile);
            $sCompare = $sContent;
            if (empty($sClass) !== true and empty($this->_aClasses[$sClass]) !== true) {
//                $aContent = explode(' */', $sContent);
//                $aContent[1] = sprintf('%snamespace %s;%s%s',PHP_EOL, substr($this->_aClasses[$sClass]['namespace'], 1), PHP_EOL,  $aContent[1]);
//                $sContent = implode(' */', $aContent);

                $pattern = '/^(\s*<\?(php?).*?\n(\/\*(.*?)\*\/\s+)?)/s';
                $sContent = preg_replace(
                    $pattern,
                    "\\1" . PHP_EOL
                    . "namespace " . substr($this->_aClasses[$sClass]['namespace'], 1) . ";" . PHP_EOL . PHP_EOL,
                    $sContent
                );

            }

            foreach ($this->_aClasses as $sFindClass => $aReplace) {
                $sReplace = ($sClass === $sFindClass) ? $aReplace['class'] : sprintf('%s\%s', $aReplace['namespace'],
                    $aReplace['class']);
                $sContent = str_replace($sFindClass, $sReplace, $sContent);
            }

            if ($this->_bAutoloaded) // search useless includes
            {
                $autoloadPattern = '/((require_once|include_once|require|include)\s*\(?\s*["\'](.*)["\']\s*\)?\s*;)(\r?)\n/i';
                preg_match_all($autoloadPattern, $sContent, $matches);

                $fileMatches = $matches[3];
                $fullCodeLines = $matches[1];
                if (count($fileMatches)) {
                    $pathPrefix = dirname($sFile) . '/';
                    foreach ($fileMatches as $index => $match) {
                        $filePath = $pathPrefix . $match;
                        if (array_key_exists($filePath, $this->_aFiles)) {
                            $tmp = $this->_aFiles[$filePath];
                            if (is_string($tmp)) {
                                $codeLine = $fullCodeLines[$index];
                                $sContent = str_replace($codeLine, '// ' . $codeLine, $sContent);
                            }
                        }

                    }
                }
            }

            if ($sCompare !== $sContent) {
                if ($this->_bWrite === true) {
                    file_put_contents($sFile, $sContent);
                } else {
                    $bumper = "============================================================" . PHP_EOL;
                    printf('%s= %s:%s%s%s%s', $bumper, $sFile, PHP_EOL, $bumper, $sContent, PHP_EOL);
                }
            }
        }
    }
}

$aOpts = getopt('d:w:p:a');
if (empty($aOpts['d']) === true) {
    $filename = $argv[0];
    $eol = PHP_EOL;

    echo "usage: $filename -p prefix -d directory [-w [false|true]] [--autoloaded]$eol$eol";
    echo " -p     The namespace prefix$eol";
    echo " -d     The base directory$eol";
    echo " -w     Write the changes to the files. Default: false$eol";
    echo " -a     If option is preset, the require and include directives will be commented out$eol";

    exit;
}

if (empty($aOpts['w']) === true) {
    $aOpts['w'] = 'false';
}

$o = new namespaceRefactor($aOpts);
$o->getFiles()->readClasses()->getNamespaces()->replace();
