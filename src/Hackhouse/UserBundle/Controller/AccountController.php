<?php
namespace Hackhouse\UserBundle\Controller;

use Hackhouse\Abstracts\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AccountController extends Controller
{
    /**
     * @Route("/profilePic", name="profilePic")
     */
    public function profilePicAction()
    {
        if ($this->getRequest()->query->has('userId')){
            $user = $this->getRepository('HackhouseUserBundle:User')->find($this->getRequest()->query->get('userId'));
        }
        else {
            $user = $this->getUser();
        }

        $size = $this->requireGetParam('type', 'small');

        //if the user is logged in and it has a profilePic
        if ($this->isLoggedIn() && $user->getProfilePic()){
            $url = $this->getService('filestore_manager')->getThumbnailUrl($user->getProfilePic(), $size);
        }
        else {
            switch($size){
                case 'large':
                    $url = '/images/nopic-large.png';
                break;

                case 'small':
                    $url = '/images/nopic-small.png';
                break;

                case 'medium':
                default:
                    $url = '/images/nopic-medium.png';
            }
        }

        return $this->redirect($url);
    }

    /**
     * @Route("/account/changeProfilePic", name="changeProfilePic")
     * @Secure(roles="ROLE_USER")
     */
    public function changeProfilePicAction()
    {
        $profilePicPath = $this->requirePostParam('profilePicPath');

        /** @var \Hackhouse\FilestoreBundle\Entity\FilestoreFileManager $filestoreManager  */
        $filestoreManager = $this->getService('filestore_manager');

        $tempfile = tempnam('/tmp', 'HackhouseTemp-');
        file_put_contents($tempfile, file_get_contents($this->getRequest()->request->get('profilePicPath')));

        $imagick = new \Imagick();
        $imagick->readImage($tempfile);

        $dims = json_decode($this->requirePostParam('profilePicDimensions'), true);
        $imagick->cropimage($dims['w'], $dims['h'], $dims['x'], $dims['y']);

        $imagick->setImageFormat( "jpg" );
        $imagick->writeImage($tempfile);
        $imagick->destroy();

        $user = $this->getUser(true);
        $thumbnailedFile = $filestoreManager->createThumbnailFile($tempfile,  $user->getId() . '/profilePic');
        $user->setProfilePic($thumbnailedFile);
        $this->persistAndFlush($user);

        return new \Symfony\Component\HttpFoundation\JsonResponse(array(
           'success' => true
        ));
    }

    /**
     * @Route("/profile", name="editProfile")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function editProfileAction()
    {
        $user = $this->getUser();
        $formUser = $this->getRepository('HackhouseUserBundle:User')->find($user->getId());
        $view = array('user' => $user);

        if ($this->getParameter('inviteCode')){
            $speakerInvitation = $this->getRepository('HackhouseSharedBundle:SpeakerInvitation')->findOneBy(array(
               'user' => $formUser->getId(),
               'code' => $this->getParameter('inviteCode')
            ));

            $view['inviteCode'] = $this->getParameter('inviteCode');
        }

        if ($this->getRequest()->isMethod('get') && isset($speakerInvitation)){
            $speakerInvitation->populateUser($formUser);
        }

        $basicAccountForm = $this->createForm(new \Hackhouse\UserBundle\Form\BasicAccountType(), $formUser);

        if ($this->isGranted(\Hackhouse\UserBundle\Entity\Group::ROLE_SPEAKER)){
            $speaker = $formUser->getSpeaker();
            $view['speaker'] = $speaker;

            if ($this->getRequest()->isMethod('get') && isset($speakerInvitation)){
                $speakerInvitation->populateSpeaker($speaker);
            }

            $speakerForm = $this->createForm(new \Hackhouse\SpeakerBundle\Form\SpeakerAccountType(), $speaker);
        }

        //ON POST
        if ($this->getRequest()->isMethod('post')){
            $formValid = $this->validateBasicAccountForm($basicAccountForm);

            if ($formValid && $this->isGranted(\Hackhouse\UserBundle\Entity\Group::ROLE_SPEAKER)){
                $formValid = $this->formPostedAndValid($speakerForm);
            }

            //PROPOGATE CHANGES AND REDIRECT
            if ($formValid){
                $user = $basicAccountForm->getData();
                $this->getEntityManager()->persist($user);

                if (isset($speaker)){
                    $speaker = $speakerForm->getData();
                    $this->getEntityManager()->persist($speaker);
                }

                $this->get('session')->setFlash('editProfileSuccess', 'Profile saved!');
                $params = array();

                if (isset($view['inviteCode'])) {
                    $params['inviteCode'] = $view['inviteCode'];
                    $params['updated'] = true;

                    $speakerInvitation->populateFromSpeaker($speaker);
                    $this->getEntityManager()->persist($speakerInvitation);
                }

                $this->getEntityManager()->flush();
                if ($this->getRequest()->request->has('updateAndRecord')){
                    if ($speakerInvitation->getTalk()) $params['talkId'] = $speakerInvitation->getTalk()->Id();
                    return $this->redirect($this->generateUrl('editTalk', $params));
                }

                return $this->redirect($this->generateUrl('editProfile', $params));
            }
        }

        $view['basicAccountForm'] = $basicAccountForm->createView();
        if (isset($speakerForm)) $view['speakerForm'] = $speakerForm->createView();
        return $view;
    }

    private function validateBasicAccountForm(\Symfony\Component\Form\Form $basicAccountForm){
        if ($this->formPostedAndValid($basicAccountForm)){
            $user = $basicAccountForm->getData();
            if ($user->getEmail()) {
                $user->setEmail(strtolower(trim($user->getEmail())));
            }

            /** @var \Hackhouse\UserBundle\Entity\UserManager $userManager */
            $userManager = $this->getService('user_manager');
            if ($userManager->emailExists($user->getEmail(), $user->getId())){
                $basicAccountForm->get('email')->addError(new \Symfony\Component\Form\FormError('This email address is already in use by another account.'));
                return false;
            }

            if ($user->getUsername() != $user->getEmail()){
                $user->setUsername($user->getEmail());
                $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                    $user, $user->getPassword(), 'secured_area', $user->getRoles()
                );
                $this->getContainer()->get('security.context')->setToken($token);

                $event = new \Symfony\Component\Security\Http\Event\InteractiveLoginEvent($this->getRequest(), $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
            }

            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @Route("/changePassword", name="changePassword")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function changePasswordAction(){
        $changePasswordForm = $this->createForm(new \Hackhouse\UserBundle\Form\ChangePasswordType());

        if ($this->getRequest()->isMethod('post')){
            if ($this->formPostedAndValid($changePasswordForm)){
                /** @var \Hackhouse\UserBundle\Entity\UserManager $userManager  */
                $userManager = $this->getService('user_manager');
                $user = $this->getUser(true);

                $data = $changePasswordForm->getData();
                $userManager->setUserPassword($user, $data['password']);
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush();

                return new \Symfony\Component\HttpFoundation\JsonResponse(array('success' => true));
            }
            else {
                return new \Symfony\Component\HttpFoundation\JsonResponse(array('success' => false,
                    'errors' => \Hackhouse\Utils\FormUtils::getFormErrors($changePasswordForm)));
            }
        }

        return array('changePasswordForm' => $changePasswordForm->createView());
    }


    /**
     * @Route("/account/unlinkThirdPartyAccount", name="unlinkThirdPartyAccount")
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function unlinkThirdPartyAccountAction(){
        $type = $this->getParameter('type');
        $user = $this->getUser();

        $account = $user->getThirdPartyAccountByType($type);
        $this->getEntityManager()->remove($account);
        $this->getEntityManager()->flush();
        return $this->redirect($this->generateUrl('editProfile'));
    }
}