<?php

namespace Hackhouse\UserBundle\Controller;

use Hackhouse\UserBundle\Form\SignupType;
use Hackhouse\UserBundle\Entity\User;

use Hackhouse\Abstracts\Controller;
use Hackhouse\AOPBundle\Annotation\Transactional;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Hackhouse\UserBundle\Entity\ActivationToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Hackhouse\UserBundle\Entity\Group;
use Symfony\Component\HttpFoundation\JsonResponse;

class SignupController extends Controller
{
    /**
     * @Route("/signup", name="signup")
     * @Template()
     */
    public function indexAction()
    {
        $form = $this->createForm(new SignupType());

        if ($this->formPostedAndValid($form)){
            $data = $form->getData();

            $userManager = $this->getService('user_manager');

            if ($userManager->emailExists($data['email'])){
                $fields = $form->all();
                $fields['email']->addError(new \Symfony\Component\Form\FormError('This email address is already in use by another account'));
            }
            else {

                //create the user
                $user = $userManager->createNewEmailUser(
                    $data['email'], $data['password'], $data['firstName'], $data['lastName']
                );

                //send activation email
                $userManager->sendActivationRequest($user);

                //log in the user
                $token = new UsernamePasswordToken($user, $user->getPassword(), 'secured_area', $user->getRoles());
                $this->getContainer()->get('security.context')->setToken($token);

                $event = new InteractiveLoginEvent($this->getRequest(), $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                //redirect the user
                return $this->redirect($this->generateUrl('firstPageAfterLogin'));
            }
        }

        return array('form' => $form->createView(), 'selectedMenuItem' => 'Sign up');
    }

    /**
     * @Route("/signup/activate", name="activate")
     * @Template()
     */
    public function activateAction()
    {
        //get the user, and get the code
        $user = $this->extractDomainObjectFromRequest('HackhouseUserBundle', 'User');
        $token = $this->requireGetParam('token');

        //compare to see if they're valid
        $userActivated = $user->getActivationToken()->getToken() == $token;

        if ($userActivated){
            $this->getEntityManager()->remove($user->getActivationToken());
            $user->setIsActive(true);
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        //put result in the twig
        return array('success' => $userActivated, 'user' => $user);
    }

    /**
     * @Route("/signup/resendActivationEmail", name="resendActivationEmail")
     * @Template()
     */
    public function resendActivationEmailAction()
    {
        //get the user, and get the code
        $user = $this->extractDomainObjectFromRequest('HackhouseUserBundle', 'User');

        $success = false;
        if ($user->isEnabled()){
            $success = false;
            $message = 'This user is already activated';
        }
        else {
            $success = true;
            $message = "New activation email sent.  Check your mail!";
            $this->getService('user_manager')->sendActivationRequest($user);
        }

        return array('success' => $success, 'message' => $message);
    }

    /**
     * @Route("/signup/chooseRole", name="chooseRole")
     * @Template()
     * @Secure(roles="ROLE_USER");
     */
    public function chooseRoleAction()
    {
        if (count($this->getUserRoles()) > 1){
            return $this->redirect($this->generateUrl('homepage'));
        }
        else if ($this->getRequest()->query->has('role')){
            $role = $this->getRequest()->query->get('role');
            $userManager = $this->getService('user_manager');

            switch($role){
                case 'supporter':
                    $userManager->addRole($this->getUser(), Group::ROLE_SUPPORTER);
                break;
                case 'hcp':
                    $userManager->addRole($this->getUser(), Group::ROLE_HCP);

                    //an hcp should be able to to support people too
                    $userManager->addRole($this->getUser(), Group::ROLE_SUPPORTER);
                break;
                default:
                    $this->setFlash('chooseRoleError', "That is not a valid role!");
                    $this->redirect($this->generateUrl('chooseRole'));
                break;
            }

            return $this->redirect($this->generateUrl('homepage'));
        }
        return array();
    }

    /**
     * @Route("/signup/setUserTimezone", name="setUserTimezone")
     * @Secure(roles="ROLE_USER");
     */
    public function setUserTimezoneAction()
    {
        $timezone = $this->requirePostParam('timezone');
        $user = $this->getUser();

        if (!empty($timezone)){
            //validate the timezone
            $timezone = timezone_open($timezone);

            $user->setTimezone($timezone->getName());
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();

            return new JsonResponse(array('success' => true));
        }
        else {
            return new JsonResponse(array('success' => false));
        }
    }


    /**
     * @Route("/emailExistsTest", name="emailExistsTest")
     * @Secure(roles="ROLE_USER");
     */
    public function emailExistsTestAction()
    {
        $email = $this->requireGetParam('email');
        $userManager = $this->getService('user_manager');

        /** @var $user User */
        $user = $userManager->findByEmail($email);

        if ($user){
            return new JsonResponse(array('success' => true, 'data' => array(
                'fullname' => $user->getFullName(),
                'id' => $user->getId(),
                'username' => $user->getUsername()
            )));
        }
        else {
            return new JsonResponse(array('success' => false));
        }
    }

}
