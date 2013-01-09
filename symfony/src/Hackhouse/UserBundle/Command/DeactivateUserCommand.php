<?php

namespace Hackhouse\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackhouse\UserBundle\Entity\User;

class DeactivateUserCommand extends AbstractUserCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('user:deactivate')
            ->setDescription('Deactivate a user.')
            ->setDefinition(array(
            new InputArgument('username', InputArgument::REQUIRED, 'The username'),
        ))
            ->setHelp(<<<EOT
The <info>user:deactivate</info> command deactivates a user:
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');

        $doctrine = $this->getContainer()->get('doctrine');
        $userRepo = $doctrine->getRepository('HackhouseUserBundle:User');

        $user = $this->lookupUserWithUsernameString($username);

        if (!$user->isEnabled()){
            $output->writeln('User is already inactive.');
        }
        else {
            $user->setIsActive(false);

            if ($user->getActivationToken() != null){
                $doctrine->getEntityManager()->remove($user->getActivationToken());
            }
            $doctrine->getEntityManager()->persist($user);
            $doctrine->getEntityManager()->flush();
            $output->writeln('User is now deactived!');
        }
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username:',
                function($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }
    }
}