<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Contracts;

use Hammerstone\Sidecar\LambdaFunction;

interface FaasClient
{
    const CREATED = 1;
    const UPDATED = 2;
    const NOOP = 3;

    /**
     * @param  LambdaFunction  $function
     * @param  null  $checksum
     * @return bool
     */
    public function functionExists(LambdaFunction $function, $checksum = null);

    public function createFunction(array $args = []);

    /**
     * @param  LambdaFunction  $function
     * @return int
     *
     * @throws Exception
     */
    public function updateExistingFunction(LambdaFunction $function);

    public function updateFunctionVariables(LambdaFunction $function);

    public function waitUntilFunctionUpdated(LambdaFunction $function);

    /**
     * Test whether the latest deployed version is the one that is aliased.
     *
     * @param  LambdaFunction  $function
     * @param $alias
     * @return bool
     */
    public function latestVersionHasAlias(LambdaFunction $function, $alias);

    /**
     * @param  LambdaFunction  $function
     * @return string
     */
    public function getLatestVersion(LambdaFunction $function);

    /**
     * @param  LambdaFunction  $function
     * @param  string  $alias
     * @param  string|null  $version
     * @return int
     */
    public function aliasVersion(LambdaFunction $function, $alias, $version = null);

    /**
     * @param  LambdaFunction  $function
     * @param  null|string  $marker
     * @return \Aws\Result
     */
    public function getVersions(LambdaFunction $function, $marker = null);

    /**
     * Delete a particular version of a function.
     *
     * @param  LambdaFunction  $function
     * @param  string  $version
     */
    public function deleteFunctionVersion(LambdaFunction $function, $version);

    public function invoke(array $args = []);

    public function invokeAsync(array $args = []);

}