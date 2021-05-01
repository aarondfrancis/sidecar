<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\Airdrop\Tests\Triggers;

use App\Imports\CsvImport;
use Hammerstone\Airdrop\Tests\BaseTest;
use Hammerstone\Airdrop\Triggers\ConfigTrigger;
use Hammerstone\Airdrop\Triggers\FileTrigger;
use Hammerstone\Sidecar\Package;
use Hammerstone\Sidecar\Tests\Support\FakeStreamWrapper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;

class PackageTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('sidecar', [
            'aws_key' => 'key',
            'aws_secret' => 'secret',
            'aws_region' => 'us-east-2',
            'aws_bucket' => 'sidecar-bucket',
        ]);
    }

    public function makePackageClass()
    {
        Storage::fake();
        FakeStreamWrapper::reset();

        $package = Mockery::mock(Package::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $package->setBasePath(__DIR__);

        $package->shouldReceive('registerStreamWrapper')->andReturnUsing(function () {
            FakeStreamWrapper::register();
        });

        return $package;
    }

    /** @test */
    public function an_exclamation_excludes()
    {
        $package = Package::make([
            'include',
            '!exclude',
        ]);

        $this->assertCount(1, $package->getIncludedPaths());
        $this->assertStringContainsString('include', $package->getIncludedPaths()[0]);

        $this->assertCount(1, $package->getExcludedPaths());
        $this->assertStringContainsString('exclude', $package->getExcludedPaths()[0]);
    }

    /** @test */
    public function it_includes_an_entire_directory()
    {
        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $files = $package->files();

        $this->assertEquals(3, $files->count());

        $files = $files
            ->map(function ($file) {
                return last(explode('/', $file));
            })
            ->sort()
            ->values()
            ->toArray();

        $this->assertEquals([
            "file1.txt",
            "file2.txt",
            "file3.txt",
        ], $files);
    }

    /** @test */
    public function it_sets_the_base_path_correctly()
    {
        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $files = $package->files();

        foreach ($files as $file) {
            $this->assertStringStartsWith(__DIR__, $file);
        }
    }

    /** @test */
    public function it_excludes_files()
    {
        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $package->exclude([
            'Support/Files/file1.txt'
        ]);

        $files = $package->files();

        $this->assertEquals(2, $files->count());

        foreach ($files as $file) {
            $this->assertStringNotContainsString('file1.txt', $file);
        }
    }

    /** @test */
    public function hashes_are_stable()
    {
        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $this->assertEquals('55ba7f7885ab81a55dd6ddda087b280b', $package->hash());
        $this->assertEquals('55ba7f7885ab81a55dd6ddda087b280b', $package->hash());
    }

    /** @test */
    public function hashes_change_based_on_file_content()
    {
        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $this->assertEquals('55ba7f7885ab81a55dd6ddda087b280b', $package->hash());

        file_put_contents(__DIR__ . '/Support/Files/file3.txt', 'Some new data');

        $this->assertEquals('f88e608b4831d7231af71f63c1839b05', $package->hash());

        file_put_contents(__DIR__ . '/Support/Files/file3.txt', '');

        $this->assertEquals('55ba7f7885ab81a55dd6ddda087b280b', $package->hash());
    }

    /** @test */
    public function it_writes_to_the_s3_stream()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $package = $this->makePackageClass();

        $package->include([
            'Support/Files'
        ]);

        $package->upload();

        $this->assertArrayHasKey('s3://sidecar-bucket/sidecar/001-55ba7f7885ab81a55dd6ddda087b280b.zip', FakeStreamWrapper::$paths);

        $contents = FakeStreamWrapper::$paths['s3://sidecar-bucket/sidecar/001-55ba7f7885ab81a55dd6ddda087b280b.zip'];

        // Write the contents to disk to inspect.
        // file_put_contents('contents.zip', $contents);

        // This hash has been manually verified to be the correct zip file.
        // $this->assertEquals('43b32cfd745d749e538cdc83fce2a966', md5($contents));

        $expected = 'UEsDBBQAAAAIAAAAIVIAAAAAAgAAAAAAAAAXAAAAU3VwcG9ydC9GaWxlcy9maWxlMi50eHQDAFBLAwQUAAAACAAAACFSAAAAAAIAAAAAAAAAFwAAAFN1cHBvcnQvRmlsZXMvZmlsZTMudHh0AwBQSwMEFAAAAAgAAAAhUgAAAAACAAAAAAAAABcAAABTdXBwb3J0L0ZpbGVzL2ZpbGUxLnR4dAMAUEsBAgMGFAAAAAgAAAAhUgAAAAACAAAAAAAAABcAAAAAAAAAAAAgAAAAAAAAAFN1cHBvcnQvRmlsZXMvZmlsZTIudHh0UEsBAgMGFAAAAAgAAAAhUgAAAAACAAAAAAAAABcAAAAAAAAAAAAgAAAANwAAAFN1cHBvcnQvRmlsZXMvZmlsZTMudHh0UEsBAgMGFAAAAAgAAAAhUgAAAAACAAAAAAAAABcAAAAAAAAAAAAgAAAAbgAAAFN1cHBvcnQvRmlsZXMvZmlsZTEudHh0UEsFBgAAAAADAAMAzwAAAKUAAAAAAA==';

        $this->assertEquals($expected, base64_encode($contents));
    }

    /** @test */
    public function if_file_already_exists_it_doesnt_make_a_new_one()
    {
        $package = $this->makePackageClass();
        $package->include([
            'Support/Files'
        ]);

        // Pretend it's already on S3
        FakeStreamWrapper::$paths = [
            's3://sidecar-bucket/sidecar/001-55ba7f7885ab81a55dd6ddda087b280b.zip' => 'fake'
        ];

        $package->upload();

        // Only 1 call to S3, to see if it exists. No calls to write anything.
        $this->assertCount(1, FakeStreamWrapper::$calls);
        $this->assertEquals('url_stat', FakeStreamWrapper::$calls[0][0]);
    }
}