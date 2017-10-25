<?php

namespace Drupal\islandora\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * @Condition(
 *   id = "islandora_object_has_relationship",
 *   label = @Translation("Check object for a relationship"),
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
class IslandoraObjectHasRelationship extends RulesConditionBase {
  protected function doEvaluate($subject, $pred_uri, $pred, $object, $type) {
    $matches = $subject->relationships->get($pred_uri, $pred, $object, $type);
    return !empty($matches);
  }
}
