<?

namespace Hackhouse\Abstracts;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Service implements ContainerAwareInterface {

    protected $container;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    /**
     * @return \TMD\UserBundle\Entity\User
     */
    public function getUser($refreshFromDb = false){
        $user = $this->getService('security.context')->getToken()->getUser();

        if ($refreshFromDb){
            $user = $this->getRepository('TMDUserBundle:User')->find($user->getId());
        }

        return $user;
    }


    public function logDebug($message){
        $this->getService('logger')->debug($message);
    }

    public function logError($message){
        $this->getService('logger')->err($message);
    }

    public function logInfo($message){
        $this->getService('logger')->info($message);
    }

    public function logNotice($message){
        $this->getService('logger')->notice($message);
    }

    /**
     * @return TwigEngine;
     */
    public function getTemplating(){
        return $this->getContainer()->get('templating');
    }

    /**
     * @return \Swift_Mailer
     */
    public function getMailer(){
        return $this->getContainer()->get('mailer');
    }

    /**
     * @return EntityRepository;
     */
    public function getRepository($name){
        return $this->getContainer()->get('doctrine')->getRepository($name);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager(){
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @param $to
     * @param $subject
     * @param $txtMessage
     * @param $htmlMessage
     * @return \Swift_Message
     */
    public function createEmailMessage($to, $subject, $txtMessage, $htmlMessage, $twigParams = array()){
        $twigParams['subject'] = $subject;
        return $this->createRawEmailMessage($to, $subject,
            $txtMessage ? $this->getTemplating()->render($txtMessage, $twigParams) : null,
            $htmlMessage ? $this->getTemplating()->render($htmlMessage, $twigParams) : null
        );
    }

    public function createRawEmailMessage($to, $subject, $txtMessage, $htmlMessage){
        $message = $this->getMailer()->createMessage()
            ->setSubject($subject)
            ->setFrom($this->getContainer()->getParameter('mailer_from'))
            ->setTo($to)
            ->setBody($txtMessage);

        if ($htmlMessage) $message->addPart($htmlMessage, 'text/html');

        return $message;
    }


        /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }


    public function getService($name){
        return $this->getContainer()->get($name);
    }

    public function persistAndFlush($entity){
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function generateUrl($route, $parameters = array(), $absolute = false){
        return $this->getService('router')->generate($route, $parameters, $absolute);
    }

    public function generateExternallyAccessibleUrl($route, $parameters = array(), $absolute = false){
        return $this->getContainer()->getParameter('externally_accessible_host') . $this->generateUrl($route, $parameters);
    }
}