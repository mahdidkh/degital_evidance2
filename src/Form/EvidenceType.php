<?php

namespace App\Form;

use App\Entity\Evidence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
 
// ajout pour le lien avec CaseWork
use App\Entity\CaseWork;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EvidenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('fileHash')
            ->add('description', TextareaType::class, ['required' => false])
            ->add('remarque', TextareaType::class, ['required' => false]) // nouveau champ
            ->add('evidenceFile', FileType::class, [
                'label' => 'Fichier (tous types autorisés)',
                'mapped' => false,
                'required' => false,
                
                 ])
                 //casework associe a evidence 
            ->add('caseWork', EntityType::class, [
                'class' => CaseWork::class, 
                'choice_label' => 'title',
                'placeholder' => 'Sélectionner un Case Work',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evidence::class,
        ]);
    }
}
