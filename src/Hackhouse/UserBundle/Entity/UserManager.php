<?
namespace Hackhouse\UserBundle\Entity;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Hackhouse\Abstracts\Service;
use Hackhouse\Utils\Utils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Hackhouse\UserBundle\Entity\AccessControlledEntity;

class UserManager extends Service {

    /**
     * @param User $user
     * @param string $role
     */
    public function removeRole(User $user, $role){
        $group = $this->getRepository('HackhouseUserBundle:Group')->findOneBy(array('role' => $role));

        if (!$group){
            throw new \Exception("Could not find role named $role");
        }

        $groups = new \Doctrine\Common\Collections\ArrayCollection();

        $user->getGroups()->removeElement($group);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $token = new UsernamePasswordToken($user, $user->getPassword(), 'secured_area', $user->getRoles());
        $this->getContainer()->get('security.context')->setToken($token);


    }

    public function hasRole(User $user, $role){
        $group = $this->getRepository('HackhouseUserBundle:Group')->findOneBy(array('role' => $role));

        if (!$group){
            throw new \Exception("Could not find role named $role");
        }

        foreach($user->getGroups() as $currentGroup){
            if ($group->equals($currentGroup)){
                return true;
            }
        }

        return false;
    }

    /**
     * @param User $user
     * @param string $role
     * @return boolean $success;
     */
    public function addRole(User $user, $role){
        $group = $this->getRepository('HackhouseUserBundle:Group')->findOneBy(array('role' => $role));

        if (!$group){
            throw new \Exception("Could not find role named $role");
        }

        if (!$this->hasRole($user, $role)){
            $user->getGroups()->add($group);
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            $token = new UsernamePasswordToken($user, $user->getPassword(), 'secured_area', $user->getRoles());
            $this->getContainer()->get('security.context')->setToken($token);

            return true;
        }
        else {
            return false;
        }
    }


    public function resetPassword($email){
        $user = $this->getRepository('HackhouseUserBundle:User')->findOneBy(array('username' => trim(strtolower($email))));

        if ($user){
            $password = Utils::generateRandomString(10);
            $this->setUserPassword($user, $password);

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            $message = $this->createEmailMessage($user->getEmail(), 'We just reset your password!',
                null,
                'HackhouseUserBundle:Email:resetPassword.html.twig',
                array('email' => $user->getEmail(), 'password' => $password)
            );

            $this->getMailer()->send($message);
        }
    }

    public function emailExists($email, $excludeId = null){
        $user = $this->findByEmail($email);

        if ($excludeId) {
            return $user != null && $user->getId() != $excludeId;
        }

        return $user != null;
    }

    public function findByEmail($email){
        return $this->getRepository('HackhouseUserBundle:User')->findOneBy(array('username' => strtolower(trim($email))));
    }

    public function sendActivationRequest(User $user){

        //create or find an activation token for user
        if($user->getActivationToken() != null){
            $activationToken = $user->getActivationToken();
        }
        else {
            $activationToken = new ActivationToken();
            $activationToken->setUser($user);
        }

        $activationToken->setToken(Utils::generateRandomString(10));
        $this->getEntityManager()->persist($activationToken);
        $this->getEntityManager()->flush($activationToken);
        $this->getEntityManager()->refresh($user);

        $message = $this->createEmailMessage($user->getEmail(), 'Activate your 10 Minute Dose account',
            null,
            'HackhouseUserBundle:Email:activate.html.twig',
            array('user' => $user)
        );

        $this->getMailer()->send($message);

    }


    /**
     * @param $email
     * @param $password
     * @param null $firstName
     * @param null $lastName
     * @param null $profilePicUrl
     * @return User
     */
    public function createNewEmailUser($email, $password, $firstName = null, $lastName = null){
        $email = trim(strtolower($email));

        return $this->createNewUser($email, $email, $password, $firstName, $lastName);
    }


    /**
     * @param $type
     * @param $uuid
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $profilePicUrl
     * @return User
     */
    public function createNewOAuthUser($type, $uuid, $email, $firstName, $lastName, $profilePicUrl){

        //if we were able to obtain the email via the third party API, use that as the username.
        //Otherwise, create a username from the uuid and third party type

        $user = $this->createNewUser($email ? $email : $type . '_' . $uuid, $email, Utils::generateRandomString(10),
            $firstName, $lastName, $profilePicUrl);

        $user->setIsActive(true);
        $this->getEntityManager()->persist($user);

        $this->attachThirdPartyAccountToUser($type, $uuid, $user);
        return $user;
    }

    public function attachThirdPartyAccountToUser($type, $uuid, User $user){
        $thirdPartyAccount = new ThirdPartyAccount();
        $thirdPartyAccount->setType($type);
        $thirdPartyAccount->setUuid($uuid);
        $thirdPartyAccount->setUser($user);

        $this->getEntityManager()->persist($thirdPartyAccount);
        $this->getEntityManager()->flush();
    }

    private function createNewUser($username, $email, $password, $firstName = null, $lastName = null,
                                   $profilePicUrl = null, $roles = array()){

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);

        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        if ($password)
            $this->setUserPassword($user, $password);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        if ($profilePicUrl == null){
            //attempt to retrieve profile pic via email
            $profilePicUrl = $user->getGravatarUrl();
        }

        //create a new job for uploading the profile pic
        $this->getService('job_server')->createJob('createFirstProfilePic', array(
            'userId' => $user->getId(),
            'profilePicUrl' => $profilePicUrl
        ));

        return $user;
    }

    public function setUserPassword(User $user, $password){
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword($password, $user->getSalt());
        $user->setPassword($password);
    }

    public function checkEntityPermissions(User $user, $permissions, AccessControlledEntity $entity, $forceQuery=false){
        if (!is_array($permissions)) $permissions = array($permissions);

        foreach($permissions as $permission){
            $ace = $this->getACE($user, $entity, $forceQuery);
            if (!$ace || !$ace->can($permission)) return false;
        }

        return true;
    }

    public function grantEntityPermission(User $user, $permission, AccessControlledEntity $entity){
        $ace = $this->getACE($user, $entity);
        if (!$ace){
            $ace = $entity->addAce($user);
        }

        $ace->addPermission($permission);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function revokeEntityPermission(User $user, $permission, AccessControlledEntity $entity){
        $ace = $this->getACE($user, $entity);
        if (!$ace) return;

        $ace->setUser($user);
        $ace->setTargetEntity($entity);
        $ace->revokePermission($permission);

        $this->getEntityManager()->persist($ace);
        $this->getEntityManager()->flush();
    }

    public function getACE(User $user, AccessControlledEntity $entity, $forceQuery=false){
        if ($forceQuery){
            var_dump($this->getRepository('\\' . get_class($entity) . "ACE")->findOneBy(array('user' => $user, 'targetEntity' => $entity)) != null);
            return $this->getRepository('\\' . get_class($entity) . "ACE")->findOneBy(array('user' => $user, 'targetEntity' => $entity));
        }
        else {
            $aces = $entity->getAces();

            foreach($aces as $ace){
                if ($ace->getUser()->getId() == $user->getId()) return $ace;
            }
        }
    }

    public function getAccessibleEntities(User $user, $entityName, $perms = array()){
        if (!is_array($perms)) $perms = array($perms);
        $where = array();

        foreach($perms as $perm) $where[$perm] = true;
        $where['user'] = $user->getId();

        return $this->getRepository($entityName . "ACE")->findBy($where);
    }

    public function getZonedTimestamp($user, $timestamp, $format=null){
        $timestamp = Utils::coerceToTimestamp($timestamp);

        $date = new \DateTime("now", new \DateTimeZone('UTC'));
        $date->setTimestamp($timestamp);

        if ($user){
            $date->setTimezone(new \DateTimeZone($user->getTimezone()));
        }

        if ($format){
            if ($format == 'DateTime') return $date;
            else return $date->format($format);
        }
        else {
            return $date->getTimestamp();
        }
    }

    public function convertFakeUTCToRealUTC($user, $timestamp, $format=null){
        $timestamp = Utils::coerceToTimestamp($timestamp);
        $date = new \DateTime("now", new \DateTimeZone("UTC"));
        $date->setTimestamp($timestamp);
        $timeAsString = $date->format(Utils::DOCTRINE_TIMESTAMP_FMT);

        $date = new \DateTime($timeAsString, new \DateTimeZone($user->getTimezone()));
	    $date->setTimezone(new \DateTimeZone("UTC"));

        if ($format){
            if ($format == 'DateTime') return $date;
            else return $date->format($format);
        }
        else {
            return $date->getTimestamp();
        }
    }
}
