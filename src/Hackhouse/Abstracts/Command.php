<?

namespace Hackhouse\Abstracts;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class Command extends ContainerAwareCommand {

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

    public function logAndWrite($str, $output){
        $output->writeln($str);
        $this->logInfo($str);
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
     * @return EntityManager
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

    public function getConfigParameter($name){
        return $this->getContainer()->getParameter($name);
    }

    public function getService($name){
        return $this->getContainer()->get($name);
    }

    public function generateExternallyAccessibleUrl($route, $parameters = array(), $absolute = false){
        return $this->getContainer()->getParameter('externally_accessible_host') . $this->generateUrl($route, $parameters);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string  $route      The name of the route
     * @param mixed   $parameters An array of parameters
     * @param Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generateUrl($route, $parameters = array(), $absolute = false)
    {
        return $this->getContainer()->get('router')->generate($route, $parameters, $absolute);
    }
}