<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Engine\RulesActionBase;
use AbstractObject;

/**
 * Rules action; remove a relationship from an object.
 *
 * @RulesAction(
 *   id = "islandora_object_remove_relationship",
 *   label = @Translation("Remove a relationship from an object"),
 *   category = @Translation("Islandora"),
 *   context = {
 *     "subject" = @ContextDefinition("islandora_object",
 *       label = @Translation("Subject"),
 *       description = @Translation("An object of which we should check the relationships (The &quot;subject&quot; of the relationship).")),
 *     "pred_uri" = @ContextDefinition("string", label = @Translation("Predicate URI")),
 *     "pred" = @ContextDefinition("string", label = @Translation("Predicate")),
 *     "object" = @ContextDefinition("string", label = @Translation("Object")),
 *     "type" = @ContextDefinition("integer", label = @Translation("Object type in the relationship")),
 *   }
 * )
 */
class ObjectRemoveRelationship extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(AbstractObject $subject, $pred_uri, $pred, $object, $type) {
    $subject->relationships->remove($pred_uri, $pred, $object, $type);
  }

}
