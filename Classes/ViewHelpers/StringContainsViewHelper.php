<?php
  namespace Slub\XmlSitemap\ViewHelpers;
  /**
   * ViewHelper to show news title
   *
   * # Example: Basic example
   * <code>
   * <xs:stringContains needle="hello", haystack="hello world">
   *  <span>About ...</span>
   * </code>
   * <output>
   * Will output "1"
   * </output>
   *
   * @package TYPO3
   * @subpackage tx_xml_sitemap
   */
  class StringContainsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Return all comments
     *
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    public function render($needle, $haystack) {

      if (strpos($haystack, $needle) === 0) {
          return 1;
      } else {
          return 0;
      }
    }
  }
