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
use Symfony\Component\Form\Extension\Core\Type\{EmailType, PasswordType, RepeatedType};
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class RegistrationFormType
 * @package App\Form
 */
class RegistrationFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' =>'Username may only contain alphanumeric characters or single hyphens, 
                    and cannot begin or end with a hyphen.']),
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
            'data_class' => User::class,
            // the name of the hidden HTML field that stores the token
            'csrf_field_name' => 'token',
            // an arbitrary string used to generate the value of the token
            // using a different string for each form improves its security
            'csrf_token_id'   => 'register_item',
        ]);
    }
}
