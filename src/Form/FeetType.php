<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\Feet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


class FeetType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('cover', FileType::class, [
                'mapped' => false,
                'data_class' => null,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'allowNull' => true
                    ]),
                    new Image([
                        'mimeTypes' => [
                            "image/png",
                            "image/jpeg",
                            "image/jpg",
                            "image/gif",
                        ],
                        'mimeTypesMessage' => 'Please upload a valid File',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('gallery', FileType::class, [
                'mapped' => false,
                'multiple' => true,
                'allow_extra_fields' => true,
                'constraints' => [
                    new All([
                        new NotBlank(),
                        new Image([
                            'mimeTypes' => [
                                "image/png",
                                "image/jpeg",
                                "image/jpg",
                                "image/gif",
                            ],
                            'mimeTypesMessage' => 'Please upload a valid File',
                        ]),
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Feet::class,
        ]);
    }
}
