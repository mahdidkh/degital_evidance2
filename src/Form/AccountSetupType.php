<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-input']
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-input']
            ])
            ->add('cin', TextType::class, [
                'label' => 'CIN',
                'attr' => ['class' => 'form-input']
            ])
            ->add('tel', TextType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-input']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => ['label' => 'Password', 'attr' => ['class' => 'form-input']],
                'second_options' => ['label' => 'Repeat Password', 'attr' => ['class' => 'form-input']],
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length(['min' => 6, 'minMessage' => 'Your password should be at least {{ limit }} characters']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Complete Setup',
                'attr' => ['class' => 'auth-btn']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
