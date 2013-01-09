<?
namespace Hackhouse\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Http\Firewall\OAuthListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class HackhouseOAuthListener extends OAuthListener {

    protected function attemptAuthentication(Request $request)
    {
        try {
            return parent::attemptAuthentication($request);
        }
        catch(Exception $e){
            //see if the request was simply denied by the user
            if ($request->query->has('error')){
                throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException();
            }

            throw $e;
        }
    }
}