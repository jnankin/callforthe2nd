<?php
namespace Hackhouse\JobServerBundle\Command\Handlers;

/**
 * Created by 10 Minute Dose
 * User: jnankin
 * Add a description here
 */
class CreateFirstProfilePicJobHandler extends AbstractJobHandler
{
    public function execute(){
        $user = $this->getRepository('TMDUserBundle:User')->find($this->getParameter('userId'));

        if (!$user) throw new \Exception("Could not find user with id=" . $this->getParameter('userId'));
        $url = $this->getParameter('profilePicUrl');

        //first try getting the image from the url
        $success = $this->createProfilePic($user, $url);

        if (!$success && $url != $user->getGravatarUrl()){
            //then try getting it from gravatar
            $success = $this->createProfilePic($user, $user->getGravatarUrl());
        }

        if (!$success){
            $this->logNotice("Could not retrieve any profile picture for username {$user->getUsername()}");
        }
    }

    public function createProfilePic($user, $url){
        $browser = new \Buzz\Browser(new \Buzz\Client\Curl());
        $response = $browser->get($url);

        //if it was successful, store it.  else, no profile pic.
        if ($response->isSuccessful()){

            $downloadPath = tempnam("/tmp", "profilePic-");
            file_put_contents($downloadPath, $response->getContent());

            $filestoreManager = $this->getService('filestore_manager');

            $squareResult = $filestoreManager->squareImage($downloadPath);
            if (!$squareResult) return false;

            $thumbnailedFile = $filestoreManager->
                createThumbnailFile($downloadPath, $user->getId() . '/profilePic');

            $user->setProfilePic($thumbnailedFile);

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            return true;
        }

        return false;
    }
}
