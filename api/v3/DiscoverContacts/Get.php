<?php

function sortContacts($a, $b) {
  if($a['is_active'] == $b['is_active']){
    if($a['school_id'] == $b['school_id']){
      return strcasecmp($a['name'], $b['name']);
    } else {
      return strcasecmp($a['school_name'], $b['school_name']);
    }
  }
  else {
    return $b['is_active'] - $a['is_active'];
  }
}

/**
 * DiscoverContacts.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_discover_contacts_get_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['is_active']['api.required'] = 1;
}

/**
 * DiscoverContacts.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_discover_contacts_get($params) {
  if (array_key_exists('id', $params) && array_key_exists('is_active', $params)) {
    $returnValues = array();
    $contactParams = array(
      "version" => 3,
      "contact_id" => $params["id"],
      "api.Relationship.get" => array("relationship_type_id" => 16, "is_active" => $params["is_active"])
    );
    $contacts = civicrm_api('Contact', 'get', $contactParams);

    foreach($contacts["values"][$params["id"]]["api.Relationship.get"]["values"] as $key => $values) {
      if($values["relationship_type_id"] == 16 && $values["is_active"] == $params["is_active"]){
        $schoolParams = array(
          "version" => 3,
          "contact_id" => $values["contact_id_b"],
          "api.Relationship.get" => array("relationship_type_id" => 10, "is_active" => 1)
        );

        $school = civicrm_api("Contact", "get", $schoolParams);
        $school_id = -1; $school_name = "";
        foreach($school["values"][$values["contact_id_b"]]["api.Relationship.get"]["values"] as $schoolKey => $relationship){
          if($relationship["is_active"] == 1 && $relationship["relationship_type_id"] == 10){
            $school_id = $relationship["contact_id_b"];
            $school_name = $relationship["display_name"];
          }
        }

        if ($school["is_error"] == 1) { $succeeded = $school["error_message"]; return $succeeded; }
        $returnValues[] = array("id" => $values["contact_id_b"], "name" => $values["display_name"], "email" => $values["email"], "relationship" => $values["id"],
          "phone" => $values["phone"], "is_active" => $values["is_active"], "school_id" => $school_id, "school_name" => $school_name);
      }
    }
    usort($returnValues, 'sortContacts');

    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
  } else {
    throw new API_Exception(/*errorMessage*/ 'Missing params id or is_active', /*errorCode*/ 1234);
  }
}

