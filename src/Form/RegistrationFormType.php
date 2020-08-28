<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, RepeatedType, TextType};
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Email()
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Password does not match.',
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9]*$/',
                        'message' => 'The password can only contain Latin characters or digits'
                    ]),
                    new NotBlank(),
                    new Length([
                        'min' => 8,
                        'max' => 50, // max length allowed by Symfony for security reasons
                    ]),
                ],
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
