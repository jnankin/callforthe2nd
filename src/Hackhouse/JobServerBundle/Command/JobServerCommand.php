<?
namespace Hackhouse\JobServerBundle\Command;
use Hackhouse\JobServerBundle\Command\Handlers as Handlers;
use Pheanstalk_Job;

class JobServerCommand extends AbstractJobServerWorker {

    public function configureWorker(){
        $this
            ->setName('jobServer:handleJob')
            ->setDescription('Handles a job from the job server')
            ->setDefinition(array());

        $this->tube = 'tmd';
    }

    public function executeJob(Pheanstalk_Job $job, $operation){

        /** @var AbstractJobHandler $handler  */
        $handler = null;
        switch($operation){
            case 'createFirstProfilePic': $handler = new Handlers\CreateFirstProfilePicJobHandler($this, $job); break;
            case 'trackEvent': $handler = new \TMD\SharedBundle\Command\Handlers\TrackEventJobHandler($this,$job); break;
            case 'spawnPortalUserCall': $handler = new \TMD\SharedBundle\Command\Handlers\SpawnPortalUserCallJobHandler($this,$job); break;
            case 'createPortalCalls': $handler = new \TMD\SharedBundle\Command\Handlers\CreatePortalCallsJobHandler($this,$job); break;
        }

        if (!$handler) throw new \Exception("Could not find a job handler for the $operation operation.");
        $handler->execute();

    }

}