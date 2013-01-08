<?php

namespace Hackhouse\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Response;
use Hackhouse\Abstracts\Controller;
use Hackhouse\UserBundle\Entity\Group;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LoginController extends Controller
{
    /**
     * @Route("/connect/{service}", name="oauthRedirectToService")
     */
    public function redirectToServiceAction($service)
    {
        if ($this->getRequest()->query->has('redirect')){
            $this->getRequest()->getSession()->set('redirect', $this->getRequest()->query->get('redirect'));
        }

        return new RedirectResponse($this->container->get('hwi_oauth.security.oauth_utils')->getAuthorizationUrl($service));
    }

    /**
     * @Route("/login", name="login")
     */
    public function loginAction()
    {
        if ($this->isLoggedIn()){
            return $this->redirect($this->generateUrl('firstPageAfterLogin'));
        }

        $request = $this->getRequest();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        if ($error){
            switch($error->getMessage()){
                case  "Bad credentials": $errorMessage = "Invalid email and password combination.  Please try again."; break;
                case  "User account is disabled.": $errorMessage = "Your account is not active.  Check your email for an activation code, or contact support."; break;
                default: $errorMessage = "Bad login.  Please try again."; break;
            }
        }

        return $this->render('HackhouseUserBundle:Login:login' . ($this->getParameter('modal') ? 'Modal' : '') . '.html.twig',
            array(
                'targetPath' => $this->getParameter('targetPath', '/login/success'),
                'last_username' => $this->getRequest()->query->has('email') ? $this->getRequest()->query->get('email') : $session->get(SecurityContext::LAST_USERNAME),
                'error'         => isset($errorMessage) ? $errorMessage : null
            )
        );
    }


    /**
     * @Route("/login/success", name="loginSuccess")
     */
    public function loginSuccessAction(){
        $this->getService('metrics_tracker')->trackAsync('login', array('user_id' => $this->getUser()->getId()));
        return $this->redirect($this->generateUrl('firstPageAfterLogin'));
    }

    /**
     * @Route("/redirectFirst", name="firstPageAfterLogin")
     */
    public function firstPageAfterLoginAction(){
        if ($this->getRequest()->getSession()->get('redirect')){
            $redirectUrl = $this->getRequest()->getSession()->get('redirect');
            $this->getRequest()->getSession()->set('redirect', null);
            return $this->redirect($redirectUrl);
        }

        if ($this->isGranted(Group::ROLE_ADMIN)){
            return $this->redirect($this->generateUrl('adminDashboard'));
        }
        else if ($this->isGranted(Group::ROLE_HCP)){
            $userManager = $this->getService('user_manager');
            $portalACEs = $userManager->getAccessibleEntities($this->getUser(),
                "HackhouseSharedBundle:Portal", \Hackhouse\UserBundle\Entity\EntityACE::EDIT);

            $numManagedPortals = 0;
            foreach($portalACEs as $ace){
                if ($ace->getTargetEntity()->getIsActive()) $numManagedPortals++;
            }

            //determine if the user manages a portal page
            if ($numManagedPortals > 0){
                //if the user manages many (i.e. is a contributor), go to some sort of listing (or the user profile page)
                if ($numManagedPortals > 1){
                    return $this->redirect($this->generateUrl('portals'));
                }
                else {
                    $slug = $portalACEs[0]->getTargetEntity()->getSlug();
                    return $this->redirect($this->generateUrl('displayPortal', array('slug' => $slug)));
                }
            }
            else {
                return $this->redirect($this->generateUrl('createPortal'));
            }


        }
        else if ($this->isGranted(Group::ROLE_SPEAKER)){
            return $this->redirect($this->generateUrl('editProfile'));
        }
        else if ($this->isGranted(Group::ROLE_SUPPORTER)){
            return $this->redirect($this->generateUrl('editProfile'));
        }
        else {
            return $this->redirect($this->generateUrl('chooseRole'));
        }
    }

    /**
     * @Route("/resetPassword", name="resetPassword")
     * @Template()
     */
    public function resetPasswordAction(){
        if ($this->isLoggedIn()){
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new \Hackhouse\UserBundle\Form\ResetPasswordType());
        if ($this->formPostedAndValid($form)){
            $data = $form->getData();
            $this->getService('user_manager')->resetPassword($data['email']);
            $this->setFlash("resetPasswordSuccess" ,
                "Your password reset request has been processed.  If we have the address you entered on file, " .
                "you'll receive a new password shortly.");

            return $this->redirect($this->generateUrl('resetPassword'));
        }
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/logout/success", name="logoutSuccess")
     * @Template()
     */
    public function logoutSuccessAction(){
        return array();
    }
}
