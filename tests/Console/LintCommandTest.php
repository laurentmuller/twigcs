<?php

namespace FriendsOfTwig\Twigcs\Tests\Console;

use FriendsOfTwig\Twigcs\Console\LintCommand;
use FriendsOfTwig\Twigcs\Container;
use FriendsOfTwig\Twigcs\TwigPort\SyntaxError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LintCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;

    public function setUp()
    {
        $container = new Container();
        $command = new LintCommand();
        $command->setContainer($container);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/exclusion/good'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 0);
        $this->assertContains('No violation found.', $output);
    }

    public function testMultipleBasePaths()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/basepaths/a', 'tests/data/basepaths/b'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertStringStartsWith('tests/data/basepaths/a/bad.html.twig', $output);
        $this->assertContains("\ntests/data/basepaths/b/bad.html.twig", $output);
    }

    public function testExecuteWithError()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/exclusion'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('ERROR', $output);
    }

    public function testExecuteWithIgnoredErrors()
    {
        $this->commandTester->execute([
            '--severity' => 'ignore',
            'paths' => ['tests/data/exclusion'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 0);
        $this->assertContains('ERROR', $output);
    }

    public function testExecuteWithIgnoredWarnings()
    {
        $this->commandTester->execute([
            '--severity' => 'error',
            'paths' => ['tests/data/exclusion/bad/warning.html.twig'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 0);
        $this->assertContains('WARNING', $output);

        $this->commandTester->execute([
            '--severity' => 'error',
            'paths' => ['tests/data/exclusion/bad'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('WARNING', $output);
    }

    public function testExecuteWithExclude()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/exclusion'],
            '--exclude' => ['bad'],
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 0);
        $this->assertContains('No violation found.', $output);
    }

    public function testErrorsOnlyDisplayBlocking()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/exclusion/bad/mixed.html.twig'],
            '--severity' => 'error',
            '--display' => LintCommand::DISPLAY_BLOCKING,
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertNotContains('l.1 c.7 : WARNING Unused variable "foo".', $output);
        $this->assertContains('l.2 c.2 : ERROR A print statement should start with 1 space.', $output);
        $this->assertContains('l.2 c.13 : ERROR There should be 0 space between the closing parenthese and its content.', $output);
        $this->assertContains('2 violation(s) found', $output);
    }

    public function testErrorsDisplayAll()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/exclusion/bad/mixed.html.twig'],
            '--severity' => 'error',
            '--display' => LintCommand::DISPLAY_ALL,
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('l.1 c.7 : WARNING Unused variable "foo".', $output);
        $this->assertContains('l.2 c.2 : ERROR A print statement should start with 1 space.', $output);
        $this->assertContains('l.2 c.13 : ERROR There should be 0 space between the closing parenthese and its content.', $output);
        $this->assertContains('3 violation(s) found', $output);
    }

    public function testSyntaxErrorThrow()
    {
        $this->expectException(SyntaxError::class);
        $this->commandTester->execute([
            'paths' => ['tests/data/syntax_error/syntax_errors.html.twig'],
            '--severity' => 'error',
            '--display' => LintCommand::DISPLAY_ALL,
            '--throw-syntax-error' => true,
        ]);

        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
    }

    public function testSyntaxErrorNotThrow()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/syntax_error/syntax_errors.html.twig'],
            '--severity' => 'error',
            '--display' => LintCommand::DISPLAY_ALL,
            '--throw-syntax-error' => false,
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('1 violation(s) found', $output);
        $this->assertContains('l.1 c.17 : ERROR Unexpected "}"', $output);
    }

    public function testSyntaxErrorNotThrowOmitArgument()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/syntax_error/syntax_errors.html.twig'],
            '--severity' => 'error',
            '--display' => LintCommand::DISPLAY_ALL,
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('1 violation(s) found', $output);
        $this->assertContains('l.1 c.17 : ERROR Unexpected "}"', $output);
    }

    public function testConfigFileWithoutCliPath()
    {
        $this->commandTester->execute([
            'paths' => null,
            '--config' => 'tests/data/config/external/.twig_cs.dist',
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('tests/data/basepaths/a/bad.html.twig
l.1 c.8 : WARNING Unused variable "foo".
tests/data/basepaths/b/bad.html.twig
l.1 c.8 : WARNING Unused variable "foo".
2 violation(s) found', $output);
    }

    public function testConfigFileWithCliPath()
    {
        $this->commandTester->execute([
            'paths' => ['tests/data/syntax_error'],
            '--config' => 'tests/data/config/external/.twig_cs.dist',
        ]);

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('tests/data/basepaths/a/bad.html.twig
l.1 c.8 : WARNING Unused variable "foo".
tests/data/basepaths/b/bad.html.twig
l.1 c.8 : WARNING Unused variable "foo".
tests/data/syntax_error/syntax_errors.html.twig
l.1 c.17 : ERROR Unexpected "}".
3 violation(s) found', $output);
    }

    public function testConfigFileSamePathWithRulesetOverrides()
    {
        chdir(__DIR__.'/../data/config/local');
        $this->commandTester->execute([
            'paths' => null,
        ]);
        chdir(__DIR__.'/../..');

        $output = $this->commandTester->getDisplay();
        $statusCode = $this->commandTester->getStatusCode();
        $this->assertSame($statusCode, 1);
        $this->assertContains('{
    "failures": 1,
    "files": [
        {
            "file": "a.html.twig",
            "violations": [
                {
                    "line": 1,
                    "column": 8,
                    "severity": 2,
                    "type": "warning",
                    "message": "Unused variable \"foo\"."
                }
            ]
        }
    ]
}', $output);
    }
}
