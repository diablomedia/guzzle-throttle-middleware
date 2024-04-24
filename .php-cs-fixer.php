<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->files()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2'                      => true,
        '@PHPUnit60Migration:risky'  => true,
        '@PHPUnit100Migration:risky' => true,
        'binary_operator_spaces'     => array('operators' => array('=' => 'align', '=>' => 'align')),
        'single_quote'               => true,
        'array_syntax'               => array('syntax' => 'short'),
        'concat_space'               => array('spacing' => 'one'),
        'psr_autoloading'            => array('dir' => 'src'),
    ))
    ->setUsingCache(true)
    ->setFinder($finder);
;
