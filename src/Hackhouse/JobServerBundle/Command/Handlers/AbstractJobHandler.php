<?php
namespace Hackhouse\JobServerBundle\Command\Handlers;
use Pheanstalk_Job;

abstract class AbstractJobHandler
{
    /** @var \Pheanstalk_Job $job */
    protected $job;
    protected $data;

    /** @var \Hackhouse\JobServerBundle\Command\AbstractJobServerWorker $worker */
    protected $worker;

    public function  __construct($worker, Pheanstalk_Job $job) {
        $this->job = $job;
        $this->data = json_decode($job->getData(), true);
        $this->worker = $worker;
    }

    abstract public function execute();

    public function getParameter($name, $default = null){
        $param = $this->getData($name);
        if (!$param) return $default;
        return $param;
    }

    public function requireParameter($name){
        $param = $this->getParameter($name);

        if (!$param) throw new \Exception("Parameter $name is required for this job");
        return $param;
    }

    public function getData($name = null){
        if ($name){
            if (isset($this->data[$name])){
                return $this->data[$name];
            }
            else {
                return null;
            }
        }
        return $this->data;
    }

    /**
     * @return Pheanstalk_Job
     */
    public function getJob(){
        return $this->job;
    }

    /***************************
     * shortcut functions
     ***************************/

    public function logDebug($message){
        return $this->worker->logDebug($message);
    }

    public function logError($message){
        return $this->worker->logError($message);
    }

    public function logInfo($message){
        return $this->worker->logInfo($message);
    }

    public function logNotice($message){
        return $this->worker->logDebug($message);
    }



    /**
     * @return TwigEngine;
     */
    public function getTemplating(){
        return $this->worker->getTemplating();
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer(){
        return $this->worker->getMailer();
    }

    /**
     * @return EntityRepository;
     */
    public function getRepository($name){
        return $this->worker->getRepository($name);
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(){
        return $this->worker->getEntityManager();
    }

    /**
     * @param $to
     * @param $subject
     * @param $txtMessage
     * @param $htmlMessage
     * @return \Swift_Message
     */
    public function createEmailMessage($to, $subject, $txtMessage, $htmlMessage, $twigParams = array()){
        return $this->worker->createEmailMessage($to, $subject, $txtMessage, $htmlMessage, $twigParams);
    }

    public function createRawEmailMessage($to, $subject, $txtMessage, $htmlMessage){
        return $this->worker->createRawEmailMessage($to, $subject, $txtMessage, $htmlMessage);
    }

    public function getService($name){
        return $this->worker->getService($name);
    }

    public function getWorker(){
        return $this->worker;
    }

}
