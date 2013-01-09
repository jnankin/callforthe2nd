<?php

namespace Hackhouse\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackhouse\UserBundle\Entity\User;

class ActivateUserCommand extends AbstractUserCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('user:activate')
            ->setDescription('Activate a user.')
            ->setDefinition(array(
            new InputArgument('username', InputArgument::REQUIRED, 'The username'),
            new InputOption('with-email', null, InputOption::VALUE_NONE, 'Dont activate if inactive right away, send an email first.'),
        ))
            ->setHelp(<<<EOT
The <info>user:activate</info> command activates a user:
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $withEmail = $input->getOption('with-email');

        $userManager = $this->getService('user_manager');

        $user = $this->lookupUserWithUsernameString($username);

        if ($user->isEnabled()){
            $output->writeln('User is already active.');
        }
        else {
            if ($withEmail){
                $userManager->sendActivationRequest($user);
                $output->writeln('Sent activation email to ' . $user->getEmail());
            }
            else {
                $user->setIsActive(true);
                $this->getEntityManager()->persist($user);
                $this->getEntityManager()->flush();
                $output->writeln('User is now active!');
            }
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