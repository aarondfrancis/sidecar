<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Clients;

use Aws\Lambda\Exception\LambdaException;
use Aws\Lambda\LambdaClient as BaseClient;
use Exception;
use Hammerstone\Sidecar\LambdaFunction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LambdaClient extends BaseClient
{
    const CREATED = 1;
    const UPDATED = 2;
    const NOOP = 3;

    /**
     * @param LambdaFunction $function
     * @return string
     */
    public function getLatestVersion(LambdaFunction $function)
    {
        return last($this->getVersions($function))['Version'];
    }

    /**
     * @param LambdaFunction $function
     * @param null|string $marker
     * @return \Aws\Result
     */
    public function getVersions(LambdaFunction $function, $marker = null)
    {
        $result = $this->listVersionsByFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'MaxItems' => 50,
            'Marker' => $marker,
        ]);

        $versions = $result['Versions'];

        if ($marker = Arr::get($result, 'NextMarker')) {
            $versions = array_merge($versions, $this->getVersions($function, $marker));
        }

        return $versions;
    }

    /**
     * @param LambdaFunction $function
     * @param string $alias
     * @param string|null $version
     * @return int
     */
    public function aliasVersion(LambdaFunction $function, $alias, $version = null)
    {
        $version = $version ?? $this->getLatestVersion($function);

        $aliased = $this->getAliasWithoutException($function, $alias);

        // The alias already exists and its the version we were trying to alias anyway.
        if ($aliased && $version === Arr::get($alias, 'FunctionVersion')) {
            return self::NOOP;
        }

        $args = [
            'FunctionName' => $function->nameWithPrefix(),
            'Name' => $alias,
            'FunctionVersion' => $version,
        ];

        if ($aliased) {
            $this->updateAlias($args);
            return self::UPDATED;
        }

        $this->createAlias($args);
        return self::CREATED;
    }

    /**
     * @param LambdaFunction $function
     * @param $name
     * @return \Aws\Result|false
     */
    public function getAliasWithoutException(LambdaFunction $function, $name)
    {
        //  Aws\Result^ {#1108
        ////  -data: array:6 [
        ////    "AliasArn" => "arn:aws:lambda:us-east-2:457434779810:function:SC-Laravel-local-Sidecar-OgImage:active"
        ////    "Name" => "active"
        ////    "FunctionVersion" => "78"
        ////    "Description" => ""
        ////    "RevisionId" => "62333798-7bd2-4456-89b1-677351020a7e"
        ////    "@metadata" => array:4 [
        ////      "statusCode" => 200
        ////      "effectiveUri" => "https://lambda.us-east-2.amazonaws.com/2015-03-31/functions/SC-Laravel-local-Sidecar-OgImage/aliases/active"
        ////      "headers" => array:5 [
        ////        "date" => "Fri, 11 Jun 2021 18:25:03 GMT"
        ////        "content-type" => "application/json"
        ////        "content-length" => "230"
        ////        "connection" => "keep-alive"
        ////        "x-amzn-requestid" => "026df39a-60cd-46eb-9c24-a3e9c2a3cc1f"
        ////      ]
        ////      "transferStats" => array:1 [
        ////        "http" => array:1 [
        ////          0 => []
        ////        ]
        ////      ]
        ////    ]
        ////  ]
        ////  -monitoringEvents: []
        try {
            return $this->getAlias([
                'FunctionName' => $function->nameWithPrefix(),
                'Name' => $name,
            ]);
        } catch (LambdaException $e) {
            if ($e->getStatusCode() !== 404) {
                throw $e;
            }
        }

        return false;
    }

    /**
     * @param LambdaFunction $function
     * @return int
     * @throws Exception
     */
    public function updateExistingFunction(LambdaFunction $function)
    {
        $config = $function->toDeploymentArray();

        // Since the code package has a unique name, this checksum
        // encompasses both the code and the configuration.
        $checksum = substr(md5(json_encode($config)), 0, 8);

        // See if the function already exists with these *exact* parameters.
        if ($this->functionExists($function, $checksum)) {
            return self::NOOP;
        }

        // Add the checksum to the description, so we can look for it next time.
        $config['Description'] .= " [$checksum]";

        // For the updateFunctionCode call, AWS requires that the S3Bucket
        // and S3Key be top level instead of nested under `Code`.
        $code = [
            'FunctionName' => $config['FunctionName'],
            'S3Bucket' => $config['Code']['S3Bucket'],
            'S3Key' => $config['Code']['S3Key'],
            'Publish' => $config['Publish']
        ];

        $config = Arr::except($config, ['Code', 'Publish']);

        $this->updateFunctionConfiguration($config);
        $this->updateFunctionCode($code);
    }

    /**
     * Delete a particular version of a function.
     *
     * @param LambdaFunction $function
     * @param string $version
     */
    public function deleteFunctionVersion(LambdaFunction $function, $version)
    {
        $this->deleteFunction([
            'FunctionName' => $function->nameWithPrefix(),
            'Qualifier' => $version
        ]);
    }

    /**
     * @param LambdaFunction $function
     * @param null $checksum
     * @return bool
     */
    public function functionExists(LambdaFunction $function, $checksum = null)
    {
        try {
            $response = $this->getFunction([
                'FunctionName' => $function->nameWithPrefix(),
            ]);
        } catch (LambdaException $e) {
            // If it's a 404, then that means the function doesn't
            // exist, which is what we're trying to figure out.
            if ($e->getStatusCode() === 404) {
                return false;
            }

            // If it's some other kind of error, we need to bail.
            throw $e;
        }

        // If the checksum is null, then we're checking to see if any
        // version of this function exists, not necessarily a
        // particular configuration of the function.
        if (is_null($checksum)) {
            return true;
        }

        // See if the description contains the checksum.
        return Str::contains(Arr::get($response, 'Configuration.Description', ''), $checksum);
    }
}
