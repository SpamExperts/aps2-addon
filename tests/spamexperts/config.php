<?php

require_once 'testbase.php';
require_once 'lib/logger.php';

class configure extends \APSTEST\TestBase
{
    public function createApplication($app_id, $endpoint_url, $package_version = null)
    {
        \APSTEST\Logger::info("Creating application instance for application #$app_id with endpoint $endpoint_url");

        $instanceProperties = $this->getParam('instanceProperties');
        $result = $this->createApplicationInstance($app_id, $endpoint_url, $instanceProperties, $package_version);

        \APSTEST\Logger::info("Application Instance is created. Instance ID: {$result['app_instance_id']}; Resource ID: {$result['app_resource_id']}");

        return $result;
    }


    public function createRTs($app_id, $instance)
    {
        $app = "SpamExperts";

        # Application Service Reference
        $RTs[] = $this->createAppServiceRefRT($app, $app_id, $instance['app_resource_id'], 1);

        # Application Services
        $RTs[] = $this->createAppServiceRT("$app Context",     $app_id, "context", 1, 1);
        $RTs[] = $this->createAppServiceRT("$app Domain",      $app_id, "domain",  0, 10);
        $RTs[] = $this->createAppServiceRT("$app Email",       $app_id, "email",   0, -1);

        # Application Counters
        $RTs[] = $this->createAppCounterRT("$app Incoming",    $app_id, "context", "incoming",            "unit", 1);
        $RTs[] = $this->createAppCounterRT("$app Outgoing",    $app_id, "context", "outgoing",            "unit", 1);
        $RTs[] = $this->createAppCounterRT("$app Archiving",   $app_id, "context", "archiving",           "unit", 1);
        $RTs[] = $this->createAppCounterRT("$app Domain Auto", $app_id, "context", "auto_protect_domain", "unit", 0);

        return $RTs;
    }
}
