<?
namespace Hackhouse\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Hackhouse\Abstracts\Service;
use Hackhouse\Utils\Utils;

class OAuthUserProvider extends Service implements OAuthAwareUserProviderInterface {
    /**
     * Loads the user by a given UserResponseInterface object.
     *
     * @param UserResponseInterface $response
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $accountType = $response->getResourceOwner()->getName();

        if ($accountType == 'google'){
            $data = $this->getInitialDataForGoogleUser($response);
        }
        else if ($accountType == 'facebook'){
            $data = $this->getInitialDataForFacebookUser($response);
        }
        else if ($accountType == 'twitter'){
            $data = $this->getInitialDataForTwitterUser($response);
        }

        if (!$data){
            throw new \Exception("There was an error retrieving the user info via oauth");
        }

        $account = $this->getRepository('HackhouseUserBundle:ThirdPartyAccount')->findOneBy(array(
            'type' => $accountType,
            'uuid' => $data['id']
        ));

        if ($account){
            $user = $account->getUser();
        }
        else {

            /** @var \Hackhouse\UserBundle\Entity\UserManager $userManager  */
            $userManager = $this->getService('user_manager');

            //if user is already logged in, see if we need to link the account
            if ($this->getService('security.context')->getToken()){
                $user = $this->getService('security.context')->getToken()->getUser();

                //add it to the user
                $userManager->attachThirdPartyAccountToUser($accountType, $data['id'], $user);
            }
            else {
                $user = $userManager->createNewOAuthUser($accountType, $data['id'], strtolower(trim($data['email'])), $data['firstName'], $data['lastName'], $data['profilePicUrl']);
            }

        }

        return $user;
    }

    private function getInitialDataForGoogleUser(UserResponseInterface $response){
        $data = array();
        $responseData = $response->getResponse();

        $data = Utils::optionsArray(
            $responseData,
            array(
                'id', 'firstName', 'lastName', 'email', 'profilePicUrl'
            ),
            array(
                'firstName' => 'given_name',
                'lastName' => 'family_name',
                'profilePicUrl' => 'picture'
            )
        );

        return $data;
    }

    private function getInitialDataForFacebookUser(UserResponseInterface $response){
        $data = array();
        $responseData = $response->getResponse();

        $data = Utils::optionsArray(
            $responseData,
            array(
                'id', 'firstName', 'lastName', 'email'
            ),
            array(
                'firstName' => 'first_name',
                'lastName' => 'last_name',
            )
        );

        $data['profilePicUrl'] = "https://graph.facebook.com/" . $responseData['id'] . "/picture?type=large";

        return $data;
    }

    private function getInitialDataForTwitterUser(UserResponseInterface $response){
        $data = array();
        $responseData = $response->getResponse();

        $data = Utils::optionsArray(
            $responseData,
            array(
                'id', 'firstName', 'profilePicUrl'
            ),
            array(
                'firstName' => 'name',
                'profilePicUrl' => 'profile_image_url'
            )
        );

        return $data;
    }


}