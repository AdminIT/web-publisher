<?php

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Controller;

use Knp\Component\Pager\Pagination\SlidingPagination;
use SWP\Bundle\CoreBundle\Context\CachedTenantContextInterface;
use SWP\Bundle\CoreBundle\Form\Type\ThemeInstallType;
use SWP\Bundle\CoreBundle\Form\Type\ThemeUploadType;
use SWP\Bundle\CoreBundle\Model\TenantInterface;
use SWP\Bundle\CoreBundle\Theme\Helper\ThemeHelper;
use SWP\Bundle\CoreBundle\Theme\Service\ThemeServiceInterface;
use SWP\Bundle\CoreBundle\Theme\Uploader\ThemeUploaderInterface;
use SWP\Component\Common\Response\ResourcesListResponse;
use SWP\Component\Common\Response\ResourcesListResponseInterface;
use SWP\Component\Common\Response\ResponseContext;
use SWP\Component\Common\Response\SingleResourceResponse;
use SWP\Component\Common\Response\SingleResourceResponseInterface;
use Sylius\Bundle\ThemeBundle\Loader\ThemeLoader;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Route;

class ThemesController extends Controller {

  private CachedTenantContextInterface $cachedTenantContext;
  private FormFactoryInterface $formFactory;
  private ThemeLoader $themeLoader;
  private ThemeServiceInterface $themeService;
  private ThemeUploaderInterface $themeUploader;

  /**
   * @param CachedTenantContextInterface $cachedTenantContext
   * @param FormFactoryInterface $formFactory
   * @param ThemeLoader $themeLoader
   * @param ThemeServiceInterface $themeService
   * @param ThemeUploaderInterface $themeUploader
   */
  public function __construct(CachedTenantContextInterface $cachedTenantContext, FormFactoryInterface $formFactory,
                              ThemeLoader         $themeLoader, ThemeServiceInterface $themeService,
                              ThemeUploaderInterface       $themeUploader) {
    $this->cachedTenantContext = $cachedTenantContext;
    $this->formFactory = $formFactory;
    $this->themeLoader = $themeLoader;
    $this->themeService = $themeService;
    $this->themeUploader = $themeUploader;
  }


  /**
   * @Route("/api/{version}/organization/themes/", options={"expose"=true}, defaults={"version"="v2"}, methods={"GET"}, name="swp_api_list_available_themes")
   */
  public function listAvailableAction(): ResourcesListResponseInterface {
    $themeLoader = $this->themeLoader;
    $themes = $themeLoader->load();
    $pagination = new SlidingPagination();
    $pagination->setItemNumberPerPage(10);
    $pagination->setCurrentPageNumber(1);
    $pagination->setItems($themes);
    $pagination->setTotalItemCount(count($themes));

    return new ResourcesListResponse($pagination);
  }

  /**
   * @Route("/api/{version}/themes/", options={"expose"=true}, defaults={"version"="v2"}, methods={"GET"}, name="swp_api_list_tenant_themes")
   */
  public function listInstalledAction(ThemeRepositoryInterface $themeRepository): ResourcesListResponseInterface {
    /** @var TenantInterface $tenant */
    $tenant = $this->cachedTenantContext->getTenant();
    $tenantCode = $tenant->getCode();
    $themes = array_filter(
        $themeRepository->findAll(),
        static function ($element) use (&$tenantCode) {
          if (strpos($element->getName(), ThemeHelper::SUFFIX_SEPARATOR . $tenantCode)) {
            return true;
          }
        }
    );

    $pagination = new SlidingPagination();
    $pagination->setItemNumberPerPage(10);
    $pagination->setCurrentPageNumber(1);
    $pagination->setItems($themes);
    $pagination->setTotalItemCount(count($themes));

    return new ResourcesListResponse($pagination);
  }

  /**
   * @Route("/api/{version}/organization/themes/", options={"expose"=true}, defaults={"version"="v2"}, methods={"POST"}, name="swp_api_upload_theme")
   */
  public function uploadThemeAction(Request $request): SingleResourceResponseInterface {
    $form = $this->formFactory->createNamed('', ThemeUploadType::class, []);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $formData = $form->getData();
      $themeUploader = $this->themeUploader;

      try {
        $themePath = $themeUploader->upload($formData['file']);
      } catch (\Exception $e) {
        return new SingleResourceResponse(['message' => $e->getMessage()], new ResponseContext(400));
      }
      $themeConfig = json_decode(file_get_contents($themePath . DIRECTORY_SEPARATOR . 'theme.json'), true);

      return new SingleResourceResponse($themeConfig, new ResponseContext(201));
    }

    return new SingleResourceResponse($form, new ResponseContext(400));
  }

  /**
   * @Route("/api/{version}/themes/", options={"expose"=true}, defaults={"version"="v2"}, methods={"POST"}, name="swp_api_install_theme")
   */
  public function installThemeAction(Request $request): SingleResourceResponseInterface {
    $form = $this->formFactory->createNamed('', ThemeInstallType::class, []);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $formData = $form->getData();
      $themeService = $this->themeService;
      [$sourceDir, $themeDir] = $themeService->getDirectoriesForTheme($formData['name']);
      $themeService->installAndProcessGeneratedData($sourceDir, $themeDir, $formData['processGeneratedData']);

      return new SingleResourceResponse(['status' => 'installed'], new ResponseContext(201));
    }

    return new SingleResourceResponse($form, new ResponseContext(400));
  }
}
