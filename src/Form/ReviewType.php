<?php

namespace App\Form;

use App\Document\Review;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'attr' => ['min' => 1, 'max' => 5]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
            ->add('isIncident', CheckboxType::class, [
                'label' => 'Signaler un incident',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
        ]);
    }
}
