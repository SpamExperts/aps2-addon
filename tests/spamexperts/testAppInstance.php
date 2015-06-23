<?php

require_once 'phpunitbase.php';

class testAppInstance extends \APSTEST\UnitBase
{
   public function testCreateApplicationInstance()
    {
        $app_id = $this->getParam('app_id');
        $endpoint_url = $this->getParam('endpoint_url');
        $package_version = $this->getParam('package_version');

        \APSTEST\Logger::info("Creating application instance for application #$app_id with endpoint $endpoint_url");

        $instanceProperties = $this->getParam('instanceProperties');
        $app_instance = $this->createApplicationInstance($app_id, $endpoint_url, $instanceProperties, $package_version);
    
        \APSTEST\Logger::debug("Instance created. " . var_export($app_instance,true));

        $this->assertArrayHasKey('app_resource_id', $app_instance);
        $this->assertArrayHasKey('app_instance_id', $app_instance);

        $this->setParam('application_instance', $app_instance);

        return $app_instance;
    }
}
