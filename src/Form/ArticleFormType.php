<?php

namespace App\Form;

use App\Entity\Article;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArticleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL, ex : ligue-des-champions-2024-25)',
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Résumé (affiché dans les listes, 500 caractères max)',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu (en Markdown — utilise ## pour les sections du sommaire)',
                'attr' => ['rows' => 18],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => array_flip(Article::CATEGORIES),
            ])
            ->add('coverImageFile', FileType::class, [
                'label' => "Image de couverture (optionnel, remplace l'actuelle)",
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : JPG, PNG, WEBP (max 4 Mo).',
                    ]),
                ],
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Mettre en avant ("à la une")',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => 'draft',
                    'Publié' => 'published',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
