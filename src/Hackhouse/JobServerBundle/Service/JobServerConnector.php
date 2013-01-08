<?php
namespace Hackhouse\JobServerBundle\Service;
use Pheanstalk;
use Hackhouse\Abstracts\Service;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class JobServerConnector extends Service implements ContainerAwareInterface
{

    protected $host;
    protected $port;
    protected $tube;

    public function __construct($host = '127.0.0.1', $port = 11300, $tube = null){
        $this->host = $host;
        $this->port = $port;
        $this->tube = $tube;
    }


    public function createJob($operation, $params = array(), $delay = Pheanstalk::DEFAULT_DELAY, $options = array()){
        $pheanstalk = $this->getPheanstalk($options);
        $params['operation'] = $operation;

        if (!isset($options['priority'])) $options['priority'] = Pheanstalk::DEFAULT_PRIORITY;
        if (!isset($options['ttr'])) $options['ttr'] = 120;

        if ($this->getTube() || $options['tube']) {
            $pheanstalk->useTube($this->getTube() ? $this->getTube() : $options['tube']);
        }

        $pheanstalk->put(json_encode($params), $options['priority'], $delay, $options['ttr']);
    }

    public function obtainJob($tube = null, $reserveTimeout = null){
        return $this->getPheanstalk()->watch($tube ? $tube : $this->getTube())->
            ignore('default')->reserve($reserveTimeout);
    }

    public function buryJob(\Pheanstalk_Job $job){
        $this->getPheanstalk()->bury($job);
    }

    public function deleteJob(\Pheanstalk_Job $job){
        $this->getPheanstalk()->delete($job);
    }

    public function getNumberOfRetries(\Pheanstalk_Job $job){
        $stats = $this->getPheanstalk()->statsJob($job);
        return $stats['releases'];
    }

    public function getStats(\Pheanstalk_Job $job){
        return $this->getPheanstalk()->statsJob($job);
    }

    public function delayJob(\Pheanstalk_Job $job, $numSeconds = 0){
        $this->getPheanstalk()->release($job, Pheanstalk::DEFAULT_PRIORITY, $numSeconds);
    }

    /**
     * @param array $options
     * @return Pheanstalk
     */
    protected function getPheanstalk($options = array()){
        if (!isset($this->pheanstalk)){
            $this->pheanstalk = new \Pheanstalk($this->getHost(), $this->getPort());
        }
        return $this->pheanstalk;
    }

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }

    public function getPort()
    {
        return $this->port;
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
