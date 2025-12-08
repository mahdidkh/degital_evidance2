<?php

namespace App\Form;

use App\Entity\ChainOfCoady;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChainOfCoadyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action')
            ->add('description')
            ->add('date_update')
            ->add('newHash')
            ->add('PreviosHash')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChainOfCoady::class,
        ]);
    }
}
