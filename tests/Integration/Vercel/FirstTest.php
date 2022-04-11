<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Tests\Integration\Vercel;

use Hammerstone\Sidecar\Sidecar;
use Hammerstone\Sidecar\Tests\BaseTest;
use Hammerstone\Sidecar\Tests\Integration\Vercel\Support\BasicVercelFunction;
use Hammerstone\Sidecar\Vercel\Client;
use Illuminate\Support\Facades\Http;

class FirstTest extends BaseTest
{

    /** @test */
    public function it_can_deploys()
    {
        Sidecar::addPhpUnitLogger();

//        $vercel = app(Client::class);
//        $function = new BasicVercelFunction;
//
//        if ($vercel->functionExists($function)) {
//            $vercel->deleteFunction($function);
//        }
//
//        $this->assertFalse($vercel->functionExists($function));
//
//        BasicVercelFunction::deploy($activate = true);
//
//        $this->assertTrue($vercel->functionExists($function));
//
//        $url = $vercel->executionUrl(new BasicVercelFunction);
//        $response = Http::get($url)->json();
//
//        $this->assertEquals('"Hello world!"', $response['result']);

        $response = BasicVercelFunction::execute();

        dd($response);
    }

}