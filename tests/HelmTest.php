<?php

namespace ProcessMaker\LaravelHelm\Tests;

use ProcessMaker\LaravelHelm\Helm;
use ProcessMaker\LaravelHelm\Tests\TestCase;
use Illuminate\Support\Str;

/**
 * @covers ProcessMaker\LaravelHelm\Helm
 * @covers ProcessMaker\LaravelHelm\HelmServiceProvider
 */
class HelmTest extends TestCase
{
    /**
     * Test Helm version function
     */
    public function testHelmVersion() : void
    {
        $version = Helm::version();
        $version->run();
        $this->assertTrue($version->isSuccessful());
        $this->assertStringContainsString('v3.', $version->getOutput());
    }

    /**
     * Test Helm install function
     */
    public function testHelmInstall() : void
    {
        $helm = Helm::install(
            'testrelease-'.Str::lower(Str::random(8)),
            'oci://registry-1.docker.io/bitnamicharts/redis',
            [
                '--version' => '18.16.1',
                'image.pullPolicy' => 'Always'
            ]
        );
        $helm->run();
        $this->assertTrue($helm->isSuccessful());
        $this->assertStringContainsString('STATUS: deployed', $helm->getOutput());
    }

    /**
     * Test Helm upgrade function
     */
    public function testHelmUpgrade() : void
    {
        $helmName = 'testrelease-'.Str::lower(Str::random(8));
        $helm = Helm::upgrade(
            $helmName,
            'oci://registry-1.docker.io/bitnamicharts/redis',
            [
                '--version' => '18.16.1',
                '--install'
            ]
        );
        $helm->run();
        $this->assertTrue($helm->isSuccessful());
        $this->assertStringContainsString('STATUS: deployed', $helm->getOutput());

        $helm = Helm::upgrade(
            $helmName,
            'oci://registry-1.docker.io/bitnamicharts/redis',
            [
                '--version' => '18.16.1'
            ]
        );
        $helm->run();
        $this->assertTrue($helm->isSuccessful());
        $this->assertStringContainsString('STATUS: deployed', $helm->getOutput());
    }

    /**
     * Test Helm delete function
     */
    public function testHelmDelete() : void
    {
        $helm = Helm::delete('testrelease-'.Str::lower(Str::random(8)));
        $helm->run();
        $this->assertFalse($helm->isSuccessful());
        $this->assertStringContainsString('release: not found', $helm->getErrorOutput());

        $releaseName = 'testrelease-'.Str::lower(Str::random(8));
        $helm = Helm::install(
            $releaseName,
            'oci://registry-1.docker.io/bitnamicharts/redis',
            [
                '--version' => '18.16.1'
            ]
        );
        $helm->run();
        $this->assertTrue($helm->isSuccessful());

        $delete = Helm::delete($releaseName);
        $delete->run();
        $this->assertTrue($delete->isSuccessful());
        $this->assertStringContainsString('release "'.$releaseName.'" uninstalled', $delete->getOutput());
    }

    public function testHelmUninstall() : void
    {
        $helm = Helm::uninstall('testrelease-'.Str::lower(Str::random(8)));
        $helm->run();
        $this->assertFalse($helm->isSuccessful());
        $this->assertStringContainsString('release: not found', $helm->getErrorOutput());

        $releaseName = 'testrelease-'.Str::lower(Str::random(8));
        $helm = Helm::install(
            $releaseName,
            'oci://registry-1.docker.io/bitnamicharts/redis',
            [
                '--version' => '18.16.1'
            ]
        );
        $helm->run();
        $this->assertTrue($helm->isSuccessful());

        $delete = Helm::uninstall($releaseName);
        $delete->run();
        $this->assertTrue($delete->isSuccessful());
        $this->assertStringContainsString('release "'.$releaseName.'" uninstalled', $delete->getOutput());
    }

    /**
     * Test Helm rawCommand function
     */
    public function testHelmRawCommand() : void
    {
        $helm = Helm::rawCommand('list');
        $helm->run();
        $this->assertTrue($helm->isSuccessful());

        $helm = Helm::rawCommand('list --all');
        $helm->run();
        $this->assertTrue($helm->isSuccessful());

        $helm = Helm::rawCommand('list --output json');
        $helm->run();
        $this->assertTrue($helm->isSuccessful());
        $this->assertIsArray(json_decode($helm->getOutput(), true));

        $helm = Helm::rawCommand('list --output json',['--all']);
        $helm->run();
        $this->assertTrue($helm->isSuccessful());
        $this->assertIsArray(json_decode($helm->getOutput(), true));
    }

    /**
     * Test Parse Options function
     */
    public function testParseOptions() : void
    {
        $options = [
            '--version' => '18.16.1',
            '--install',
            'wait',
            '-n' => 'default',
            '-o',
            'app.url' => 'http://localhost'
        ];
        $parsed = [
            '--version',
            '18.16.1',
            '--install',
            '--wait',
            '-n',
            'default',
            '-o',
            '--set',
            'app.url=http://localhost'
        ];
        $helm = Helm::delete('abc', $options);
        $commandLineArray = $this->parseCommandLine($helm->getCommandLine());

        $this->assertTrue(count(array_intersect($parsed,$commandLineArray)) === count($parsed));
    }

    /**
     * Test Parse Environments function
     */
    public function testParseEnvironments() : void
    {
        $envs = [
            'KUBECONFIG' => '/path/to/kubeconfig',
            'NAMESPACE' => 'default'
        ];
        $helm = Helm::delete('abc', [], $envs);
        $processEnvs = $helm->getEnv();
        $this->assertEquals($envs, $processEnvs);

        $badEnvs = [
            'KUBECONFIG' => '/path/to/kubeconfig',
            'NAMESPACE' => 'default',
            'BAD_ENVIRONMENT'
        ];
        $helm = Helm::delete('abc', [], $badEnvs);
        $processEnvs = $helm->getEnv();
        $this->assertEquals($envs, $processEnvs);
    }

    /**
     * Parse the command line
     */
    private function parseCommandLine($commandLine): array
    {
        $args = explode(' ', $commandLine);
        return array_map(function($arg) {
            return Str::replace("'", "", $arg);
        }, $args);
    }
}
