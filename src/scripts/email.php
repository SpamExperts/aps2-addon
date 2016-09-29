<?php
require_once "vendor/autoload.php";
require_once "aps/2/runtime.php";

/**
 * Class email
 * @type("http://aps.spamexperts.com/app/email/1.0")
 * @implements("http://aps-standard.org/types/core/resource/1.0")
 */
class email extends \APS\ResourceBase
{
    ## Strong link with the context
    /**
     * @link("http://aps.spamexperts.com/app/context/2.2")
     * @required
     * 
     * @var context
     */
    public $context;

    ## Strong link to SE domain
    /**
     * @link("http://aps.spamexperts.com/app/domain/1.0")
     * @required
     */
    public $domain;

    # Strong link with the service user
    /**
     * @link("http://aps-standard.org/types/core/service-user/1.0")
     * @required
     */
    public $email;

    ## User's email address
    /**
     * @type(string)
     * @title("Email")
     * @description("User's email address")
     * @required
     */
    public $name;

    ## Email user protection status
    /**
     * @type(boolean)
     * @title("User sync status")
     * @description("Whether the user is synced with SpamExperts filters")
     * @required
     */
    public $status;

    /** @var $logger Logger */
    private $logger;
    /** @var $report Report */
    private $report;
    private $API;

    public function __construct($API = NULL, $APSC = NULL)
    {
        parent::__construct();

        $this->API = $API;
        $this->APSC = $APSC;
        $this->report = new Report($this->logger = new Logger("Email"));
    }

    public function provision()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        if ($this->status) {
            $this->logger->info(__FUNCTION__ . ": Provisioned with true protection status. Requesting protection... ");
            $this->context->emailProtect(json_encode(array($this->email->aps->id)));
        }

        $this->logger->info(__FUNCTION__ . ": stop");
    }

    public function unprovision()
    {
        $this->logger->info(__FUNCTION__ . ": start");

        $this->logger->info(__FUNCTION__ . ": Removing SE email user resource");
        $this->API()->removeEmailUser($this->name);

        $this->logger->info(__FUNCTION__ . ": stop");
    }


    ###


    public function API()
    {
        if (!$this->API) {
            $this->API = new APIClient($this->context->service);
            $this->API->setDefaultOption('auth', array($this->context->username, $this->context->password));
        }
        return $this->API;
    }
    
    /**
     * @verb(GET)
     * @path("/controlPanelLoginLink")
     * @return(string)
     */
    public function getControlPanelLoginLink()
    {
        $this->logger->info(__METHOD__ . ": start");

        $ticket = $this->context->getAuthTicket($this->name);

        $this->logger->info(__METHOD__ . ": stop");

        return $ticket;
    }

}
