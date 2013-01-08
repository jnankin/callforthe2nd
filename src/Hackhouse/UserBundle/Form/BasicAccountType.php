<?php

namespace Hackhouse\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BasicAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('firstName')
            ->add('lastName')
            ->add('timezone', 'choice', array(
                'choices' => array('America/Los_Angeles' => 'Pacific', 'America/Phoenix' => 'Mountain', 'America/Chicago' => 'Central', 'America/New_York' => 'Eastern')
            ))
            ->add('phoneNumber')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'data_class' => 'Hackhouse\UserBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'basic_account';
    }
}
