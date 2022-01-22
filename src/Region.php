<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Sidecar;

class Region
{
    const US_EAST_1 = 'us-east-1';             // US East (N. Virginia)
    const US_EAST_2 = 'us-east-2';             // US East (Ohio)
    const US_WEST_1 = 'us-west-1';             // US West (N. California)
    const US_WEST_2 = 'us-west-2';             // US West (Oregon)
    const CA_CENTRAL_1 = 'ca-central-1';       // Canada (Central)
    const EU_CENTRAL_1 = 'eu-central-1';       // EU (Frankfurt)
    const EU_WEST_1 = 'eu-west-1';             // EU (Ireland)
    const EU_WEST_2 = 'eu-west-2';             // EU (London)
    const EU_WEST_3 = 'eu-west-3';             // EU (Paris)
    const EU_NORTH_1 = 'eu-north-1';           // EU (Stockholm)
    const EU_SOUTH_1 = 'eu-south-1';           // EU (Milan)
    const AP_EAST_1 = 'ap-east-1';             // Asia Pacific (Hong Kong)
    const AP_SOUTH_1 = 'ap-south-1';           // Asia Pacific (Mumbai)
    const AP_NORTHEAST_3 = 'ap-northeast-3';   // Asia Pacific (Osaka)
    const AP_NORTHEAST_2 = 'ap-northeast-2';   // Asia Pacific (Seoul)
    const AP_SOUTHEAST_1 = 'ap-southeast-1';   // Asia Pacific (Singapore)
    const AP_SOUTHEAST_2 = 'ap-southeast-2';   // Asia Pacific (Sydney)
    const AP_NORTHEAST_1 = 'ap-northeast-1';   // Asia Pacific (Tokyo)
    const AF_SOUTH_1 = 'af-south-1';           // Africa (Cape Town)
    const CN_NORTH_1 = 'cn-north-1';           // China (Beijing)
    const CN_NORTHWEST_1 = 'cn-northwest-1';   // China (Ningxia)
    const ME_SOUTH_1 = 'me-south-1';           // Middle East (Bahrain)
    const SA_EAST_1 = 'sa-east-1';             // South America (São Paulo)

    public static function all()
    {
        return (new \ReflectionClass(static::class))->getConstants();
    }
}
