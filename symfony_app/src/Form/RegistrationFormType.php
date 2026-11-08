<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $input = 'w-full rounded-md border border-[#2a2828] bg-[#121214] px-4 py-3 text-[#efefef] placeholder-[#5f5b5b] outline-none transition focus:border-[#4fb3a6]';
        $label = 'mb-1.5 block text-xs uppercase tracking-wide text-[#7d7b7b]';

        $builder
            ->add('username', TextType::class, [
                'label' => 'Login',
                'label_attr' => ['class' => $label],
                'attr' => [
                    'class' => $input,
                    'autocomplete' => 'username',
                    'placeholder' => 'np. anna',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Podaj login.'),
                    new Assert\Length(min: 3, max: 180),
                    new Assert\Regex(
                        pattern: '/^[a-zA-Z0-9_.-]+$/',
                        message: 'Dozwolone są litery, cyfry, kropka, myślnik i podkreślenie.',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Hasło',
                    'label_attr' => ['class' => $label],
                    'attr' => ['class' => $input, 'autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Powtórz hasło',
                    'label_attr' => ['class' => $label],
                    'attr' => ['class' => $input, 'autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Hasła muszą być takie same.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Podaj hasło.'),
                    new Assert\Length(min: 8, minMessage: 'Hasło musi mieć min. 8 znaków.'),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Wyrażam zgodę na przetwarzanie mojego głosu w celu weryfikacji tożsamości.',
                'mapped' => false,
                'label_attr' => ['class' => 'text-sm leading-6 text-[#b4b0b0]'],
                'attr' => ['class' => 'mt-1 h-4 w-4 shrink-0 rounded border-[#2a2828] bg-[#121214] accent-[#4fb3a6]'],
                'constraints' => [
                    new Assert\IsTrue(message: 'Zgoda jest wymagana.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
