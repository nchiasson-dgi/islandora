<?php

namespace Drupal\islandora\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * @RulesAction(
 *   id = "islandora_object_add_relationship",
 *   label = @Translation("Add a relationship to an object"),
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
class IslandoraObjectAddRelationship extends RulesActionBase {
  protected function doExecute($subject, $pred_uri, $pred, $object, $type) {
    $subject->relationships->add($pred_uri, $pred, $object, $type);
  }
}
