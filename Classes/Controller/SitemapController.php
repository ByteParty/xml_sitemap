<?php
namespace Slub\XmlSitemap\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Slub\XmlSitemap\Domain\Repository\SitemapRepository;
use Slub\XmlSitemap\Domain\Repository\KitodoDocumentRepository;
use GeorgRinger\News\Domain\Repository\NewsRepository;

/**
 * Class SitemapController
 */
class SitemapController extends ActionController
{

    /**
     * @var SitemapRepository
     */
    protected $sitemapRepository;

    /**
     * @param SitemapRepository $sitemapRepository
     */
    public function injectSitemapRepository(SitemapRepository $sitemapRepository) {
        $this->sitemapRepository = $sitemapRepository;
    }

    /**
     * kitodoDocumentRepository
     *
     * @var \Slub\XmlSitemap\Domain\Repository\KitodoDocumentRepository
     */
    protected $kitodoDocumentRepository;

    /**
     * @param KitodoDocumentRepository $kitodoDocumentRepository
     */
    public function injectKitodoDocumentRepository(KitodoDocumentRepository $kitodoDocumentRepository) {
        $this->kitodoDocumentRepository = $kitodoDocumentRepository;
    }

    /**
     * @var \GeorgRinger\News\Domain\Repository\NewsRepository
     */
    protected $newsRepository;

    /**
    * @var int $currentPage
    */
    protected $currentPage = NULL;


    /**
    * @var int $currentPid
    */
    protected $currentPid = 0;


    /**
     * Initializes the current action
     *
     * @return void
     */
    public function initializeListKitodoAction() {

        // set storagePid to point extbase to the right repositories
        $configurationArray = [
            'persistence' => [
                'storagePid' => $this->settings['kitodoRecordStorage'],
            ],
        ];
        $configurationArray = $this->settings + $configurationArray;
        $this->configurationManager->setConfiguration($configurationArray);

    }

    /**
     * Initializes the current action
     *
     * @return void
     */
    public function initializeListNewsAction() {

      if (ExtensionManagementUtility::isLoaded('news')) {
          // set storagePid to point extbase to the right repositories
          $configurationArray = [
              'persistence' => [
                  'storagePid' => $this->settings['newsRecordStorage'],
              ],
          ];
          $configurationArray = $this->settings + $configurationArray;
          $this->configurationManager->setConfiguration($configurationArray);

          // instantiate and fill the news repository
          $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
          $this->newsRepository = $objectManager->get(NewsRepository::class);
      }
    }

    /**
     * Initializes the current action
     *
     * @return void
     */
    public function initializeAction() {

        // set output format to xml if configured
        if (isset($this->settings['format'])) {

          $this->request->setFormat($this->settings['format']);

        }

        $this->currentPage = GeneralUtility::_GET('page');
        $this->currentPid = $this->settings['websiteRoot'] ? $this->settings['websiteRoot'] : $GLOBALS['TSFE']->id;
    }

    /**
     * default list action
     *
     * @return void
     */
     public function listAction() {

      $action = $this->getParametersSafely('action');

      switch ($action) {
          case 'pages':
              $this->forward('listPages');
              break;
          case 'news':
              $this->forward('listNews');
              break;
          case 'kitodo':
              $this->forward('listKitodo');
              break;
          default:
              $this->forward('index');
      }

    }

    /**
     * show sitemap index
     *
     * @return void
     */
    public function indexAction()
    {
        // prepare standard pages sitemap
        $sitemaps[] = array(
            'name' => 'listPages',
            'entries' => $this->getPaginationArray($this->sitemapRepository->getSubPages($this->currentPid))
        );

        if (ExtensionManagementUtility::isLoaded('news')) {
            // prepare news sitemap
            $this->initializeListNewsAction();
            $sitemaps[] = array(
                'name' => 'listNews',
                'entries' => $this->getPaginationArray($this->newsRepository->findAll())
            );
        }

        // prepare kitodo sitemap
        $this->initializeListKitodoAction();
        $sitemaps[] = array(
            'name' => 'listKitodo',
            'entries' => $this->getPaginationArray($this->kitodoDocumentRepository->findAll())
        );

        // append configured custom sitemaps
        if (is_array($this->settings['customSitemaps'])) {
            foreach($this->settings['customSitemaps'] as $customSitemap) {
                $customSitemaps[] = $customSitemap;
            }
        }

        $this->view->assign('sitemaps', $sitemaps);
        $this->view->assign('customSitemaps', $customSitemaps);
    }

    /**
     * show urlsets of pages
     *
     * @return void
     */
    public function listPagesAction()
    {
        $pages = $this->sitemapRepository->getSubPages($this->currentPid);
        $pageSlice = array_slice($pages, $this->currentPage * $this->settings['list']['paginate']['itemsPerPage'], $this->settings['list']['paginate']['itemsPerPage']);
        $this->view->assign('pages', $pageSlice);
        $this->view->assign('rootPageId', $this->currentPid);
    }

    /**
     * show urlsets of news
     *
     * @return void
     */
    public function listNewsAction()
    {
        if ($this->newsRepository instanceof NewsRepository) {
            $news = $this->newsRepository->findAll();
            $pageSlice = array_slice($news->toArray(), $this->currentPage * $this->settings['list']['paginate']['itemsPerPage'], $this->settings['list']['paginate']['itemsPerPage']);
            $this->view->assign('pages', $pageSlice);
            $this->view->assign('rootPageId', $this->currentPid);
        } else {
            throw new \Exception('NewsRepository not available. Check your installation.');
        }
    }

    /**
     * show urlsets of news
     *
     * @return void
     */
    public function listKitodoAction()
    {
        $recordNum = $this->kitodoDocumentRepository->countAll();
        $kitodoDocuments = $this->kitodoDocumentRepository->findAllLimitOffset($this->settings['list']['paginate']['itemsPerPage'], $this->currentPage * $this->settings['list']['paginate']['itemsPerPage']);
        $this->view->assign('pages', $kitodoDocuments);
        $this->view->assign('rootPageId', $this->currentPid);
    }

    /**
     * Safely gets Parameters from request
     * if they exist
     *
     * @param string $parameterName
     *
     * @return null|string
     */
    protected function getParametersSafely($parameterName)
    {
        if ($this->request->hasArgument($parameterName)) {
            return $this->request->getArgument($parameterName);
        }
        return null;
    }

    /**
     * get empty array for pagination
     *
     * @param array $pages
     *
     * @return array
     */
    protected function getPaginationArray($pages)
    {
      for ($i = 0; $i < ceil(count($pages)/$this->settings['list']['paginate']['itemsPerPage']); $i++) {
          $count[] = $i;
      }
      return $count;
    }

}
