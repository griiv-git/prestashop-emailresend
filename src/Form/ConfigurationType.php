<?php
/**
 * Copyright since 2024 Griiv
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    Griiv
 * @copyright Since 2024 Griiv
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL") v. 3.0
 */

declare(strict_types=1);

namespace Griiv\EmailResend\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ConfigurationType extends TranslatorAwareType
{
    private const TRANSLATION_DOMAIN = 'Modules.Griivemailresend.Admin';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('store_attachments', SwitchType::class, [
                'label' => $this->trans('Store attachments', self::TRANSLATION_DOMAIN),
                'help' => $this->trans('Enable to store email attachments for resending.', self::TRANSLATION_DOMAIN),
                'required' => false,
            ])
            ->add('storage_mode', ChoiceType::class, [
                'label' => $this->trans('Storage mode', self::TRANSLATION_DOMAIN),
                'help' => $this->trans('Choose how to store attachments.', self::TRANSLATION_DOMAIN),
                'choices' => [
                    $this->trans('Database (BLOB)', self::TRANSLATION_DOMAIN) => 'database',
                    $this->trans('File system', self::TRANSLATION_DOMAIN) => 'file',
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('max_size', IntegerType::class, [
                'label' => $this->trans('Max attachment size (MB)', self::TRANSLATION_DOMAIN),
                'help' => $this->trans('Maximum size per attachment in megabytes.', self::TRANSLATION_DOMAIN),
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
                'constraints' => [
                    new NotBlank(),
                    new Range([
                        'min' => 1,
                        'max' => 100,
                        'notInRangeMessage' => $this->trans('Value must be between {{ min }} and {{ max }}.', self::TRANSLATION_DOMAIN),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => self::TRANSLATION_DOMAIN,
        ]);
    }
}
