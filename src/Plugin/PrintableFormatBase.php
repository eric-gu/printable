<?php

/**
 * @file
 * Contains \Drupal\printable\Plugin\PrintableFormatBase.
 */

namespace Drupal\printable\Plugin;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\printable\LinkExtractor\LinkExtractorInterface;
use Drupal\Core\Render\Element\Html;
use Drupal\printable\PrintableCssIncludeInterface;
use Drupal\Core\Form\FormStateInterface;
use Wa72\HtmlPageDom\HtmlPage;
use Wa72\HtmlPageDom\HtmlPageCrawler;
 
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a base class for Filter plugins.
 */
abstract class PrintableFormatBase extends PluginBase implements PrintableFormatInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Printable CSS include manager.
   *
   * @var \Drupal\printable\PrintableCssIncludeInterface
   */
  protected $printableCssInclude;

  /**
   * Printable link extractor.
   *
   * @var \Drupal\printable\LinkExtractor\LinkExtractorInterface
   */
  protected $linkExtractor;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\printable\PrintableCssIncludeInterface $printable_css_include
   *   The printable CSS include manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $config_factory, PrintableCssIncludeInterface $printable_css_include, LinkExtractorInterface $link_extractor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->printableCssInclude = $printable_css_include;
    $this->linkExtractor = $link_extractor;
    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id,  $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory'),
      $container->get('printable.css_include'),
      $container->get('printable.link_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->configFactory->get('printable.format')->set($this->getPluginId(), $this->configuration)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function setContent(array $content) {
    $this->content = $content;
    $this->footer_content= NULL;
    if ($this->configFactory->get('printable.settings')->get('list_attribute')) {
      $this->footer_content = $this->linkExtractor->listAttribute((string) render($this->content));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return new Response($this->getOutput());
  }

  /**
   * Build a render array of the content, wrapped in the printable theme.
   *
   * @return array
   *  A render array representing the themed output of the content.
   */
  protected function buildContent() {
    $build = array(
      '#theme' => array('printable__' . $this->getPluginId(), 'printable'),
      '#header' => array(
        '#theme' => array('printable_header__' . $this->getPluginId(), 'printable_header'),
        '#logo_url' => theme_get_setting('logo.url'),
      ),
      '#content' => $this->content,
      '#footer' => array(
        '#theme' => array('printable_footer__' . $this->getPluginId(), 'printable_footer'),
        '#footer_content' => $this->footer_content,
      ),
      '#attached' => array(
        '#library' => array(
          '#css' => array(drupal_get_path('module', 'printable') . '/css/drupal-printable.css'),
          '#js'  => array('core/jquery'),
        ),
      ),
    );

    if ($include_path = $this->printableCssInclude->getCssIncludePath()) {
      $build['#attached']['css'][] = $include_path;
    }

    // @todo remove this so we can unit test this method.
    // system_page_build($build);
    return $build;
  }

  /**
   * Get the HTML output of the whole page, ready to pass to the response
   * object.
   *
   * @return string
   *   The HTML string representing the output of this printable format.
   */
  protected function getOutput() {
    $content = $this->buildContent();
    $rendered_page = render($content);
   if ($this->configFactory->get('printable.settings')->get('extract_links')) {
      $rendered_page = $this->linkExtractor->extract((string) $rendered_page);
    }
    else {
      $rendered_page = $this->linkExtractor->removeAttribute((string) $rendered_page,'href');
    }
    return $rendered_page;
  }

}
