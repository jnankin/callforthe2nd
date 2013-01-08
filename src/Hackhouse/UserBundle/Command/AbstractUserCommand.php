<?
namespace Hackhouse\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Hackhouse\Abstracts\Command;

abstract class AbstractUserCommand extends Command {

    /**
     * @param $username
     * @return \Hackhouse\UserBundle\Entity\User;
     * @throws \Exception
     */
    protected function lookupUserWithUsernameString($username){

        $userRepo = $this->getRepository('HackhouseUserBundle:User');
        $userManager = $this->getService('user_manager');

        if (is_numeric($username)){
            $user = $userRepo->find($username);
        }
        else {
            $user = $userRepo->findOneBy(array('username' => $username));
        }

        if ($user == null){
            throw new \Exception('Could not find user identified by ' . $username);
        }

        return $user;
    }

}