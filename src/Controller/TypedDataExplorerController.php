<?php

namespace Drupal\typed_data_explorer\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\webprofiler\Helper\ClassShortenerInterface;
use Drupal\webprofiler\Helper\IdeLinkGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TypedDataExplorerController.
 *
 * @package Drupal\typed_data_explorer\Controller
 */
class TypedDataExplorerController extends ControllerBase {

  /**
   * @var \Drupal\webprofiler\Helper\IdeLinkGeneratorInterface
   */
  private $ideLinkGenerator;

  /**
   * @var \Drupal\webprofiler\Helper\ClassShortenerInterface
   */
  private $classShortener;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  private $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('entity_type.manager'),
      $container->get('webprofiler.ide_link_generator'),
      $container->get('webprofiler.class_shortener')
    );
  }

  /**
   * TypedDataExplorerController constructor.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\webprofiler\Helper\IdeLinkGeneratorInterface $ideLinkGenerator
   * @param \Drupal\webprofiler\Helper\ClassShortenerInterface $classShortener
   */
  public function __construct(TypedDataManagerInterface $typedDataManager, EntityTypeManagerInterface $entityTypeManager, IdeLinkGeneratorInterface $ideLinkGenerator, ClassShortenerInterface $classShortener) {
    $this->typedDataManager = $typedDataManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->ideLinkGenerator = $ideLinkGenerator;
    $this->classShortener = $classShortener;
  }

  /**
   * @return array
   */
  public function listAction() {
    $definitions = $this->typedDataManager->getDefinitions();

    $rows = [];
    foreach ($definitions as $key => $definition) {
      $row = [];
      $row[] = $key;
      $row[] = $this->getClassLink($definition['class']);
      $row[] = $this->getClassLink($definition['definition_class']);
      $row[] = Link::fromTextAndUrl('Explore', new Url('typed_data_explorer.plugin', ['plugin' => $key]));

      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Id'),
        $this->t('Class'),
        $this->t('Definition class'),
        $this->t('Action')
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * @param $plugin
   *
   * @return array
   */
  public function pluginAction($plugin) {
    $definition = $this->typedDataManager->getDefinition($plugin);

    $rows = [];
    foreach ($definition as $key => $value) {
      $row = [];
      $row[] = $key;
      $row[] = $this->formatValue($value);

      $rows[] = $row;
    }

    $build[] = [
      '#markup' => $plugin,
    ];

    $build[] = [
      '#type' => 'table',
      '#header' => [$this->t('Key'), $this->t('Value')],
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * @param $entity_type
   * @param $id
   *
   * @return array
   */
  public function entityAction($entity_type, $id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);

    $fieldDefinitions = $entity->getFieldDefinitions();

    $rows = [];
    /** @var FieldDefinitionInterface $definition */
    foreach ($fieldDefinitions as $definition) {
      $row = [];
      $row[] = $definition->getLabel();
      $row[] = $definition->getName();
      $row[] = $definition->getType();
      $row[] = $definition->getDescription();
      $row[] = $definition->getDataType();
      $row[] = $this->getClassLink($definition->getClass());
      $row[] = $definition->getTargetEntityTypeId();
      $row[] = $definition->getTargetBundle();
      $row[] = $definition->getConstraints();
      $row[] = Link::fromTextAndUrl('Explore', new Url('typed_data_explorer.entity_property', [
        'entity_type' => $entity_type,
        'id' => $id,
        'name' => $definition->getName()
      ]));
      $rows[] = $row;
    }

    $build[] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Description'),
        $this->t('Data type'),
        $this->t('Class'),
        $this->t('Entity'),
        $this->t('Bundle'),
        $this->t('Constraints'),
        $this->t('Action'),
      ],
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * @param $entity_type
   * @param $id
   * @param $name
   *
   * @return array
   */
  public function entityPropertyAction($entity_type, $id, $name) {
    /** @var FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);

    /** @var FieldDefinitionInterface $fieldDefinition */
    $fieldDefinition = $entity->getFieldDefinition($name);

    /** @var FieldItemListInterface $typedData */
    $typedData = $entity->get($name);

    /** @var FieldStorageDefinitionInterface|DataDefinitionInterface $itemDefinition */
    $itemDefinition = $fieldDefinition->getItemDefinition();

//    dpm($typedData); // typed data
//    dpm($fieldDefinition); //data definition
//    dpm($itemDefinition->getDataType()); // data type plugin name

    $rows = [];
    /**
     * @var string $key
     * @var DataDefinitionInterface $definition
     */
    foreach ($itemDefinition->getPropertyDefinitions() as $key => $definition) {
      $row = [];
      $row[] = $key;
      $row[] = Link::fromTextAndUrl($definition->getDataType(), new Url('typed_data_explorer.plugin', ['plugin' => $definition->getDataType()]));
      $row[] = $this->formatValue($typedData->{$key});

      $rows[] = $row;
    }

    $build[] = [
      '#markup' =>
        '<ul><li>' . new FormattableMarkup('The Data Definition of @name is @definition.', [
          '@name' => $name,
          '@definition' => $this->getClassLink(get_class($fieldDefinition)),
        ]) .
        '</li><li>' . new FormattableMarkup('The Typed Data of @name is @typed.', [
          '@name' => $name,
          '@typed' => $this->getClassLink(get_class($typedData)),
        ]) .
        '</li><li>' . new FormattableMarkup('The Typed Data plugin id is @plugin.', [
          '@plugin' => Link::fromTextAndUrl($itemDefinition->getDataType(), new Url('typed_data_explorer.plugin', ['plugin' => $itemDefinition->getDataType()]))->toString(),
        ]) .
        '</li></ul>',
    ];

    $build[] = [
      '#type' => 'table',
      '#header' => [$this->t('Property'), $this->t('Type'), $this->t('Value')],
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * @return array
   */
  public function constraintsAction() {
    $constraintManager = \Drupal::service('validation.constraint');

    $rows = [];
    foreach ($constraintManager->getDefinitions() as $key => $definition) {
      $row = [];
      $row[] = $definition['label'];
      $row[] = $this->formatValue($definition['class']);
      $row[] = $definition['id'] ?? '-';
      $row[] = $definition['provider'] ?? '-';

      $rows[] = $row;
    }

    $build[] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Class'),
        $this->t('Id'),
        $this->t('Provider'),
      ],
      '#rows' => $rows,
    ];

    return $build;
  }

  public function testAction() {
    $definition = DataDefinition::create('string')
      ->addConstraint('Length', ['max' => 5]);
    $string_typed_data1 = \Drupal::typedDataManager()
      ->create($definition, 'my string', 'test');
    dpm($string_typed_data1);

    $constraintManager = \Drupal::service('validation.constraint');
    $definitions = $constraintManager->getDefinitions();
    dpm($definitions);

    $node = Node::load(2);
    $node->setTitle('TestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTestTest');
    $valid = $node->validate();
    dpm($valid);

    return [
      '#markup' => 'test',
    ];
  }

  /**
   * @param $value
   *
   * @return \Drupal\typed_data_explorer\Controller\TypedDataExplorerController|string
   */
  private function formatValue($value) {
    if (is_bool($value)) {
      return ($value) ? 'True' : 'False';
    }

    if (is_array($value)) {
      return print_r($value, TRUE);
    }

    if (is_object($value)) {
      return $this->getClassLink(get_class($value));
    }

    if (class_exists($value)) {
      return $this->getClassLink($value);
    }

    return $value;
  }

  /**
   * @param $class
   *
   * @return static
   */
  private function getClassLink($class) {
    $reflectedClass = new \ReflectionClass($class);

    $link = $this->ideLinkGenerator->generateLink($reflectedClass->getFileName(), 0);
    $text = $this->classShortener->shortenClass($class);

    return new FormattableMarkup('<a href="@link">@text</a>', [
      '@link' => $link,
      '@text' => $text
    ]);
  }

}
