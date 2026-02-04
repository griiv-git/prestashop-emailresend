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

namespace Griiv\EmailResend\Controller\Admin;

use Griiv\EmailResend\Form\ConfigurationType;
use Griiv\EmailResend\Repository\EmailAttachmentRepository;
use Griiv\EmailResend\Repository\EmailContentRepository;
use Griiv\EmailResend\Service\EmailResendService;
use Griiv\EmailResend\Service\OrphanCleanerService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailResendController extends FrameworkBundleAdminController
{
    private const TRANSLATION_DOMAIN = 'Modules.Griivemailresend.Admin';
    private EmailContentRepository $contentRepository;
    private EmailAttachmentRepository $attachmentRepository;
    private EmailResendService $resendService;
    private OrphanCleanerService $orphanCleaner;

    public function __construct(
        EmailContentRepository $contentRepository,
        EmailAttachmentRepository $attachmentRepository,
        EmailResendService $resendService,
        OrphanCleanerService $orphanCleaner
    ) {
        $this->contentRepository = $contentRepository;
        $this->attachmentRepository = $attachmentRepository;
        $this->resendService = $resendService;
        $this->orphanCleaner = $orphanCleaner;
    }

    /**
     * Module configuration page
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function configurationAction(Request $request): Response
    {
        $form = $this->createForm(ConfigurationType::class, [
            'store_attachments' => (bool) \Configuration::get('GRIIV_EMAILRESEND_STORE_ATTACHMENTS'),
            'storage_mode' => \Configuration::get('GRIIV_EMAILRESEND_STORAGE_MODE') ?: 'database',
            'max_size' => (int) \Configuration::get('GRIIV_EMAILRESEND_MAX_SIZE') ?: 10,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            \Configuration::updateValue('GRIIV_EMAILRESEND_STORE_ATTACHMENTS', (int) $data['store_attachments']);
            \Configuration::updateValue('GRIIV_EMAILRESEND_STORAGE_MODE', $data['storage_mode']);
            \Configuration::updateValue('GRIIV_EMAILRESEND_MAX_SIZE', (int) $data['max_size']);

            $this->addFlash('success', $this->trans('Settings updated successfully.', self::TRANSLATION_DOMAIN));

            return $this->redirectToRoute('admin_griiv_email_resend_configuration');
        }

        return $this->render('@Modules/griivemailresend/views/templates/admin/configuration.html.twig', [
            'form' => $form->createView(),
            'layoutHeaderToolbarBtn' => [],
            'layoutTitle' => $this->trans('Email Resend Configuration', self::TRANSLATION_DOMAIN),
            'enableSidebar' => true,
            'help_link' => false,
        ]);
    }

    /**
     * Clean orphan records
     *
     * @AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")
     */
    public function cleanOrphansAction(): Response
    {
        $deleted = $this->orphanCleaner->clean();

        $this->addFlash(
            'success',
            $this->trans('%count% orphan records deleted.', self::TRANSLATION_DOMAIN, ['%count%' => $deleted])
        );

        return $this->redirectToRoute('admin_griiv_email_resend_configuration');
    }

    /**
     * Index action (hidden, used for tab registration)
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function indexAction(): Response
    {
        return $this->redirectToRoute('admin_griiv_email_resend_configuration');
    }

    /**
     * AJAX: Get email content for preview
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function getContentAction(Request $request): JsonResponse
    {
        $idMail = (int) $request->request->get('id_mail', 0);

        if ($idMail <= 0) {
            return $this->json([
                'success' => false,
                'message' => $this->trans('Invalid email ID', self::TRANSLATION_DOMAIN),
            ]);
        }

        $content = $this->contentRepository->findByMailId($idMail);

        if (!$content) {
            return $this->json([
                'success' => false,
                'message' => $this->trans('Content not available (email sent before module installation)', self::TRANSLATION_DOMAIN),
            ]);
        }

        $hasAttachments = $this->attachmentRepository->hasAttachments($idMail);

        return $this->json([
            'success' => true,
            'html_content' => $content->getHtmlContent(),
            'has_attachments' => $hasAttachments,
        ]);
    }

    /**
     * AJAX: Get list of active employees
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     */
    public function getEmployeesAction(): JsonResponse
    {
        $employees = \Employee::getEmployees(true);
        $result = [];

        foreach ($employees as $emp) {
            $employee = new \Employee((int) $emp['id_employee']);
            $result[] = [
                'id' => (int) $emp['id_employee'],
                'name' => $emp['firstname'] . ' ' . $emp['lastname'],
                'email' => $employee->email,
            ];
        }

        return $this->json([
            'success' => true,
            'employees' => $result,
        ]);
    }

    /**
     * AJAX: Resend email
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     */
    public function resendAction(Request $request): JsonResponse
    {
        $idMail = (int) $request->request->get('id_mail', 0);
        $emails = $request->request->get('emails', []);
        $includeAttachments = (bool) $request->request->get('include_attachments', false);

        // Validate
        if (!is_array($emails) || empty($emails)) {
            return $this->json([
                'success' => false,
                'message' => $this->trans('No recipient specified', self::TRANSLATION_DOMAIN),
            ]);
        }

        if (count($emails) > 10) {
            return $this->json([
                'success' => false,
                'message' => $this->trans('Maximum 10 recipients allowed', self::TRANSLATION_DOMAIN),
            ]);
        }

        foreach ($emails as $email) {
            if (!\Validate::isEmail($email)) {
                return $this->json([
                    'success' => false,
                    'message' => $this->trans('Invalid email address: %email%', self::TRANSLATION_DOMAIN, ['%email%' => $email]),
                ]);
            }
        }

        // Get content
        $content = $this->contentRepository->findByMailId($idMail);
        if (!$content) {
            return $this->json([
                'success' => false,
                'message' => $this->trans('Content not found', self::TRANSLATION_DOMAIN),
            ]);
        }

        // Send email
        try {
            $sent = $this->resendService->resend($idMail, $emails, $includeAttachments);

            if ($sent) {
                return $this->json([
                    'success' => true,
                    'message' => $this->trans('Email sent successfully', self::TRANSLATION_DOMAIN),
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => $this->trans('Failed to send email', self::TRANSLATION_DOMAIN),
            ]);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('GriivEmailResend error: ' . $e->getMessage(), 3);

            return $this->json([
                'success' => false,
                'message' => $this->trans('Error: %error%', self::TRANSLATION_DOMAIN, ['%error%' => $e->getMessage()]),
            ]);
        }
    }
}
