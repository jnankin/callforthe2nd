<?php
namespace Hackhouse\JobServerBundle\Command;
use Hackhouse\Abstracts\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackhouse\JobServerBundle\Exception\JobServerConnectionException;
use Hackhouse\JobServerBundle\Exception\AbortJobException;
use Exception;
use Pheanstalk_Job;

abstract class AbstractJobServerWorker extends Command
{
    protected $reserveTimeout = 120;
    protected $tube = null;
    protected $maxJobRetries = 3;
    protected $jobDelay = 20;

    /**
     * @see Command
     */
    protected function configure(){
        $this->configureWorker();
    }

    abstract function configureWorker();

    protected function execute(InputInterface $input, OutputInterface $output){
        try {
            $this->obtainAndProcessJob();
        }
        catch (JobServerConnectionException $e){
            $this->logError("There was trouble connecting to pheanstalk - were going to wait {$this->reserveTimeout} seconds and then try again.");
            $this->logError($e->getMessage());
            $this->logError($e->getTraceAsString());
            sleep($this->reserveTimeout);
        }

        $tubeMessage = $this->tube ? "for {$this->tube}" : "";
        $this->logDebug("Job worker $tubeMessage exiting.");
    }

    protected function obtainAndProcessJob(){

        /** @var \Hackhouse\JobServerBundle\Service\JobServerConnector $jobServer  */
        $jobServer = $this->getJobServer();

        $job = $jobServer->obtainJob($this->tube, $this->reserveTimeout);
        if (!$job) return false;

        $this->logDebug("Got job! " . var_export($job, true));

        $data = json_decode($job->getData(), true);

        if (!$data['operation']) {
            $errMessage = "Job was not formatted correctly.  No operation provided! Burying. {$this->tube}:{$job->getId()} - {$job->getData()}";
            $jobServer->buryJob($job);
            $this->logError($errMessage);
            throw new \Exception($errMessage);
        }

        try {
            $this->executeJob($job, $data['operation']);
            $jobServer->deleteJob($job);
        }
        catch (AbortJobException $e){
            $this->logError("Problem executing job {$job->getId()}: {$e->getMessage()}, so burying it immediately.");
            $this->logError($e->getTraceAsString());
            $jobServer->buryJob($job);
        }
        catch (Exception $e){
            $this->logError("There was a problem executing job {$job->getId()}: {$e->getMessage()} ");
            $this->logError($e->getTraceAsString());

            $numRetries = $jobServer->getNumberOfRetries($job);
            if ($numRetries < $this->getMaxJobRetries()){
                $jobServer->delayJob($job, $this->getJobDelay());
                $this->logError("Delaying job for " . $this->getJobDelay() . " seconds.");
            }
            else {
                $this->logError("We've already tried this job $numRetries times.  Burying it for human inspection!");
                $jobServer->buryJob($job);
            }
        }

        return true;
    }

    /**
     * @abstract
     * @param Pheanstalk_Job $job
     * @param $operation
     */
    abstract protected function executeJob(Pheanstalk_Job $job, $operation);

    public function getJobServer(){
        return $this->getService('job_server');
    }

    public function setJobDelay($jobDelay)
    {
        $this->jobDelay = $jobDelay;
    }

    public function getJobDelay()
    {
        return $this->jobDelay;
    }

    public function setMaxJobRetries($maxJobRetries)
    {
        $this->maxJobRetries = $maxJobRetries;
    }

    public function getMaxJobRetries()
    {
        return $this->maxJobRetries;
    }

    public function setReserveTimeout($reserveTimeout)
    {
        $this->reserveTimeout = $reserveTimeout;
    }

    public function getReserveTimeout()
    {
        return $this->reserveTimeout;
    }

    public function setTube($tube)
    {
        $this->tube = $tube;
    }

    public function getTube()
    {
        return $this->tube;
    }


}
