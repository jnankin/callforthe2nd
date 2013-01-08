<?php

namespace Hackhouse\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hackhouse\UserBundle\Entity\User;

class CreateUserCommand extends AbstractUserCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Create a user.')
            ->setDefinition(array(
            new InputArgument('username', InputArgument::REQUIRED, 'The username'),
            new InputArgument('password', InputArgument::REQUIRED, 'The password'),
            new InputOption('super-admin', null, InputOption::VALUE_NONE, 'Set the user as super admin'),
            new InputOption('inactive', null, InputOption::VALUE_NONE, 'Set the user as inactive'),
        ))
            ->setHelp(<<<EOT
The <info>user:create</info> command creates a user:

<info>php app/console user:create jnankin@gmail.com</info>

This interactive shell will ask you for a password.

You can alternatively specify the password as the second argument:

<info>php app/console user:create matthieu@example.com mypassword</info>

You can create a super admin via the super-admin flag:

<info>php app/console user:create admin --super-admin</info>

You can create an inactive user (will not be able to log in):

<info>php app/console user:create thibault --inactive</info>

EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $superadmin = $input->getOption('super-admin');

        $userManager = $this->getContainer()->get('user_manager');

        //create the user
        $user = $userManager->createNewEmailUser(
            $username, $password
        );

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));
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

        if (!$input->getArgument('password')) {
            $password = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a password:',
                function($password) {
                    if (empty($password)) {
                        throw new \Exception('Password can not be empty');
                    }

                    return $password;
                }
            );
            $input->setArgument('password', $password);
        }
    }
}