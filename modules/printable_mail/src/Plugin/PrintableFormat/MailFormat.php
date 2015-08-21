<?php

/**
 * @file
 * Contains \Drupal\printable_pdf\Plugin\PrintableFormat\MailFormat.
 */

namespace Drupal\printable_pdf\Plugin\PrintableFormat;

use Drupal\printable\Plugin\PrintableFormatBase;
use Drupal\printable\Annotation\PrintableFormat;
use Drupal\printable\PrintableCssIncludeInterface;
use Drupal\printable\LinkExtractor\LinkExtractorInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a plugin to display a PDF version of a page.
 *
 * @PrintableFormat(
 *   id = "mail",
 *   module = "printable_mail",
 *   title = @Translation("Mail"),
 *   description = @Translation("Mail description.")
 * )
 */
class MailFormat extends PrintableFormatBase {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   *
   * @param \Drupal\pdf_api\PdfGeneratorPluginManager $pdf_generator_manager
   *   The PDF generator plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $config_factory, PdfGeneratorPluginManager $pdf_generator_manager, PrintableCssIncludeInterface $printable_css_include, LinkExtractorInterface $link_extractor) {
    parent::__construct($configuration,$plugin_id, $plugin_definition, $config_factory, $printable_css_include,$link_extractor);
    $config = $this->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.pdf_generator'),
      $container->get('printable.css_include'),
      $container->get('printable.link_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
    );
  }
  public function calculateDependencies(){}

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $options = array();
    foreach ($this->pdfGeneratorManager->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['title'];
    }
    $form['pdf_generator'] = array(
      '#type' => 'radios',
      '#title' => 'PDF Generator',
      '#default_value' => $config['pdf_generator'],
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state){}


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration(array(
      'pdf_generator' => $form_state['values']['pdf_generator'],
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function buildContent() {
   $content = parent::buildContent();
   $rendered_page = parent::extractLinks(render($content));
   $this->pdfGenerator->addPage($rendered_page);
  }


  /**
   * {@inheritdoc}
   */
  public function getResponse() {
  }

}
