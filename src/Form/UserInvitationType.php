<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class UserInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
                'label' => 'Email Address',
                'attr' => ['class' => 'form-input']
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Investigateur' => 'investigateur',
                    'Supervisor' => 'supervisor',
                ],
                'label' => 'Role',
                'attr' => ['class' => 'form-input']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Generate Invitation Link',
                'attr' => ['class' => 'auth-btn']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
