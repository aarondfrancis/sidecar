<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar\Contracts;

use Hammerstone\Sidecar\ServerlessFunction;

interface FaasClient
{
    const CREATED = 1;
    const UPDATED = 2;
    const NOOP = 3;

    /**
     * @param  ServerlessFunction  $function
     * @param  null  $checksum
     * @return bool
     */
    public function functionExists(ServerlessFunction $function, $checksum = null);

    public function createNewFunction(ServerlessFunction $function);

    /**
     * @param  ServerlessFunction  $function
     * @return int
     *
     * @throws Exception
     */
    public function updateExistingFunction(ServerlessFunction $function);

    public function updateFunctionVariables(ServerlessFunction $function);

    public function waitUntilFunctionUpdated(ServerlessFunction $function);

    /**
     * Test whether the latest deployed version is the one that is aliased.
     *
     * @param  ServerlessFunction  $function
     * @param $alias
     * @return bool
     */
    public function latestVersionHasAlias(ServerlessFunction $function, $alias);

    /**
     * @param  ServerlessFunction  $function
     * @return string
     */
    public function getLatestVersion(ServerlessFunction $function);

    /**
     * @param  ServerlessFunction  $function
     * @param  string  $alias
     * @param  string|null  $version
     * @return int
     */
    public function aliasVersion(ServerlessFunction $function, $alias, $version = null);

    /**
     * @param  ServerlessFunction  $function
     * @param  null|string  $marker
     * @return \Aws\Result
     */
    public function getVersions(ServerlessFunction $function, $marker = null);

    /**
     * Delete a particular version of a function.
     *
     * @param  ServerlessFunction  $function
     * @param  string  $version
     */
    public function deleteFunctionVersion(ServerlessFunction $function, $version);

    public function invoke(array $args = []);

    public function invokeAsync(array $args = []);

}