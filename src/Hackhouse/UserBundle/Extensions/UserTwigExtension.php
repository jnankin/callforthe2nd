<?

namespace Hackhouse\UserBundle\Extensions;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\FormView;
use Hackhouse\Utils\Utils;

class UserTwigExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'zoned_timestamp' => new \Twig_Filter_Method($this, 'zonedTimestamp')
        );
    }

    public function getFunctions()
    {
        return array(
            'has_entity_permission' => new \Twig_Function_Method($this, 'hasEntityPermission'),
            'logged_in' => new \Twig_Function_Method($this, 'loggedIn'),
        );
    }

    public function hasEntityPermission($object, $permission){
        if (!$this->loggedIn()) return false;

        $user = $this->container->get('security.context')->getToken()->getUser();
        return $this->container->get('user_manager')->checkEntityPermissions($user, $permission, $object);
    }

    public function loggedIn(){
        $token = $this->container->get('security.context')->getToken();
        $isAnonymous = $token instanceof \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
        return !$isAnonymous;
    }

    public function zonedTimestamp($timestamp, $format=null){
        return $this->container->get('user_manager')->getZonedTimestamp(
            $this->loggedIn() ? $this->container->get('security.context')->getToken()->getUser() : null,
            $timestamp,
            $format
        );
    }


    // for a service we need a name
    public function getName()
    {
        return 'user_twig_extension';
    }
    
    public function setContainer($container){
        $this->container = $container;
    }
}
