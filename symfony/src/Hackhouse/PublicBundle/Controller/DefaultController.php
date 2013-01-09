<?php

namespace Hackhouse\PublicBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Hackhouse\PublicBundle\Entity\ApplicationSetting;
use Hackhouse\Abstracts\Controller;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function homepageAction()
    {
        return array('settings' => $this->getRepository('HackhousePublicBundle:ApplicationSetting')->findAll());
    }
}
