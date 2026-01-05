<?php

global $_GESTOR;

$_GESTOR['module-id']							=	'module_id';
$_GESTOR['module#'.$_GESTOR['module-id']] = json_decode(file_get_contents(__DIR__ . '/module_id.json'), true);

// ===== Auxiliary Interfaces

function module_id_auxiliary_interface(){
	
}

// ===== Main Interfaces

function module_id_add(){
	global $_GESTOR;
	
	$module = $_GESTOR['module#'.$_GESTOR['module-id']]; // Module configurations defined in `MODULE_ROOT/module_id.json`
	
	// ===== Save record in Database
	
	if(isset($_GESTOR['add-database'])){
		$user = manager_user(); // Logged in user data.
		
		// ===== Mandatory fields validation
		
		interface_validation_mandatory_fields(Array(
			'fields' => Array(
				Array(
					'rule' => 'text-mandatory', // text-mandatory | selection-mandatory | email-mandatory
					'field' => 'post-or-get-field-name', // POST or GET field of the form.
					'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'id-variable-name')), // Field label.
				),
			)
		));
		
		// ===== Identifier definition
		
		$fields = null;
		$field_without_single_quotes = false;
		
		$id = database_identifier(Array( // Creates unique `id` inside the target table of this module based on the received field, in this case `name`.
			'id' => database_escape_field($_REQUEST["name"]),
			'table' => Array(
				'name' => $module['table']['name'],
				'field' => $module['table']['id'],
				'id_name' => $module['table']['numeric_id'],
			),
		));
		
		// ===== Verify if sent fields do not exist in the database
		
		$existsField = interface_verify_fields(Array(
			'field' => 'field', // Case you need to verify a specific unique field besides `id` above.
			'value' => database_escape_field($_REQUEST['field']),
		));
		
		if($existsField){ // If unique field exists in database, return error and ask user to change.
			$alert = manager_variables(Array('module' => 'interface','id' => 'alert-there-is-a-field'));
			$alert = model_var_replace_all($alert,"#label#",manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-path-label')));
			$alert = model_var_replace($alert,"#value#",database_escape_field($_REQUEST['field']));
			
			interface_alert(Array(
				'redirect' => true,
				'msg' => $alert
			));
			
			manager_redirect($_GESTOR['module-id'].'/add/'); // Redirects again to the add form.
		}
		
        // Assembly of fields that will be included in the database table.

		// ===== Standard fields
		
		$field_name = "name"; $post_name = "name"; 					        			if($_REQUEST[$post_name])		$fields[] = Array($field_name,database_escape_field($_REQUEST[$post_name]));
		$field_name = "id"; $field_value = $id; 										$fields[] = Array($field_name,$field_value,$field_without_single_quotes);

		// ===== Specific fields

		$field_name = "fields"; $post_name = $field_name; 								if($_REQUEST[$post_name])		$fields[] = Array($field_name,database_escape_field($_REQUEST[$post_name]));
		
		// ===== Common fields
		
		$field_name = $module['table']['status']; $field_value = 'A'; 					$fields[] = Array($field_name,$field_value,$field_without_single_quotes);
		$field_name = $module['table']['version']; $field_value = '1'; 					$fields[] = Array($field_name,$field_value,$field_without_single_quotes);

        // Insertion in database
		database_insert_name
		(
			$fields,
			$module['table']['name']
		);
		
		manager_redirect($_GESTOR['module-id'].'/edit/?'.$module['table']['id'].'='.$id); // Redirects to record editing.
	}
	
	// ===== Inclusion of custom CSS and JS besides the standard that is already in `module_id.js`
	
	// $_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="<URL>" />';
	// $_GESTOR['javascript'][] = '<script src="<URL>"></script>';
	
	// ===== Module JS Inclusion: `module_id.js`
	
	manager_page_javascript_include();
	
	// ===== Interface add finish options
	
	$_GESTOR['interface']['add']['finish'] = Array(
		'form' => Array( // Form control options
			'validation' => Array( // Form fields validation options.
				Array(
					'rule' => 'text-mandatory', // Standard rule applied to the field.
					'field' => 'name', // Field name in the form.
					'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-name-label')), // Field label in the form.
					'identifier' => 'name', // Form field identifier.
				)
			),
			'fields' => Array( // Dynamic form fields assembly like select. Example: select of module records.
				Array(
					'type' => 'select', // Field type.
					'id' => 'module', // Field identifier.
					'name' => 'module', // Form field name.
					'search' => true, // Activate search in field.
					'clean' => true, // Option to clean select.
					'selectClass' => 'class', // CSS class of select.
					'placeholder' => manager_variables(Array('restart' => $_GESTOR['module-id'],'module' => $_GESTOR['module-id'],'id' => 'form-module-placeholder')), // Select label.
					'table' => Array( // Table where data will be searched.
						'name' => 'modules', // Table name.
						'field' => 'name', // Field of records that will be placed in select options.
						'numeric_id' => 'id', // Record reference.
						'where' => "module_group_id!='libraries'", // Condition to filter records.
					),
				),
			)
		)
	);
}

function module_id_edit(){
	global $_GESTOR;
	
	$module = $_GESTOR['module#'.$_GESTOR['module-id']]; // Module configurations defined in `MODULE_ROOT/module_id.json`
	
	// ===== Identifier of the record that will be edited.
	
	$id = $_GESTOR['module-record-id'];
	
	// ===== Definition of database fields to edit.
	
	$databaseFields = Array(
		'name',
	);
	
	$databaseFieldsStandard = Array(
		$module['table']['status'],
		$module['table']['version'],
		$module['table']['creation_date'],
		$module['table']['modification_date'],
	);
	
	$databaseFieldsEdit = array_merge($databaseFields,$databaseFieldsStandard);
	$databaseFieldsBefore = $databaseFields;
	
	// ===== Save Updates in Database
	
	if(isset($_GESTOR['update-database'])){
		// ===== Recover database data state before editing.
		
		if(!database_select_fields_before_start( // Gets field values before updating database for before => after comparison.
			database_fields_commas($databaseFieldsBefore)
			,
			$module['table']['name'],
			"WHERE ".$module['table']['id']."='".$id."'"
			." AND ".$module['table']['status']."!='D'"
		)){ // If there is any problem with the record, redirect and alert user of the problem.
			interface_alert(Array(
				'redirect' => true,
				'msg' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'alert-database-field-before-error'))
			));
			
			manager_redirect_root();
		}
		
		// ===== Mandatory fields validation

		interface_validation_mandatory_fields(Array( // Definition of all fields that are mandatory. Besides defining them in JS, confirm here in PHP, to avoid user trying to put not allowed data using some subterfuge.
			'fields' => Array(
				Array(
					'rule' => 'text-mandatory', // Rule, in this case only filling obligation.
					'field' => 'name', // Field name.
					'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-name-label')), // Field label.
				)
			)
		));
		
		// ===== Verify if sent fields do not exist in the database
		
		$existsField = interface_verify_fields(Array(
			'field' => 'field', // Case you need to verify a specific unique field besides `id` above.
			'value' => database_escape_field($_REQUEST['field']),
		));
		
		if($existsField){ // If unique field exists in database, return error and ask user to change.
			$alert = manager_variables(Array('module' => 'interface','id' => 'alert-there-is-a-field'));
			$alert = model_var_replace_all($alert,"#label#",manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-path-label')));
			$alert = model_var_replace($alert,"#value#",database_escape_field($_REQUEST['field']));
			
			interface_alert(Array(
				'redirect' => true,
				'msg' => $alert
			));
			
			manager_redirect($_GESTOR['module-id'].'/edit/?'.$module['table']['id'].'='.$id); // Redirects again to the edit form.
		}
		
		// ===== Table default values and rules for name field
		
		$edit = Array(
			'table' => $module['table']['name'],
			'extra' => "WHERE ".$module['table']['id']."='".$id."' AND ".$module['table']['status']."!='D'",
		);
		
		$field_name = "name"; $request_name = 'name'; $changes_name = 'name'; if(database_select_fields_before($field_name) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$edit['data'][] = $field_name."='" . database_escape_field($_REQUEST[$request_name]) . "'"; if(!isset($_REQUEST['_gestor-no-change-id'])){$change_id = true;} $changes[] = Array('field' => 'form-'.$changes_name.'-label', 'value_before' => database_select_fields_before($field_name),'value_after' => database_escape_field($_REQUEST[$request_name]));}
		
		// ===== If name changes, change record identifier
		
		if(isset($change_id)){
            $id_new = database_identifier(Array(
                'id' => database_escape_field($_REQUEST["name"]),
                'table' => Array(
                    'name' => $module['table']['name'],
                    'field' => $module['table']['id'],
                    'id_name' => $module['table']['numeric_id'],
                    'id_value' => $layouts[0][$module['table']['numeric_id']],
                ),
            ));
            
            $changes_name = 'id'; $changes[] = Array('field' => 'field-id', 'value_before' => $id,'value_after' => $id_new);
            $field_name = $module['table']['id']; $edit['data'][] = $field_name."='" . $id_new . "'";
            $_GESTOR['module-record-id'] = $id_new;
		}
		
		// ===== Update of other fields.
		
		$field_name = "field"; $request_name = $field_name; $changes_name = 'field'; if(database_select_fields_before($field_name) != (isset($_REQUEST[$request_name]) ? $_REQUEST[$request_name] : NULL)){$edit['data'][] = $field_name."='" . database_escape_field($_REQUEST[$request_name]) . "'"; $changes[] = Array('field' => 'form-'.$changes_name.'-label', 'value_before' => database_select_fields_before($field_name),'value_after' => database_escape_field($_REQUEST[$request_name]));}
		
		// ===== If there were changes, modify in database along with standard update fields
		
		if(isset($edit['data'])){
			$field_name = $module['table']['version']; $edit['data'][] = $field_name." = ".$field_name." + 1";
			
			$edit['sql'] = database_fields_commas($edit['data']);
			
			if($edit['sql']){
				database_update
				(
					$edit['sql'],
					$edit['table'],
					$edit['extra']
				);
			}
			$edit = false;
			
			// ===== Include fields in backup.
			
			if(isset($backups)){
				foreach($backups as $backup){
					interface_backup_field_include(Array(
						'numeric_id' => interface_module_variable_value(Array('variable' => $module['table']['numeric_id'])),
						'version' => interface_module_variable_value(Array('variable' => $module['table']['version'])),
						'field' => $backup['field'],
						'value' => $backup['value'],
					));
				}
			}
			
			// ===== Include changes in history.
			
			interface_history_include(Array(
				'id' => $id,
				'table' => Array(
					'name' => $module['table']['name'],
					'numeric_id' => $module['table']['numeric_id'],
					'version' => $module['table']['version'],
				),
				'changes' => $changes,
			));
		}
		
		// ===== Reread URL with updated data.
		
		manager_redirect($_GESTOR['module-id'].'/edit/?'.$module['table']['id'].'='.(isset($id_new) ? $id_new : $id));
	}
	
	// ===== Inclusion of custom CSS and JS besides the standard that is already in `module_id.js`
	
	// $_GESTOR['css'][] = '<link rel="stylesheet" type="text/css" media="all" href="<URL>" />';
	// $_GESTOR['javascript'][] = '<script src="<URL>"></script>';
	
	// ===== Module JS Inclusion: `module_id.js`
	
	manager_page_javascript_include();
	
	// ===== Select data from database
	
	$return_db = database_select_edit
	(
		database_fields_commas($databaseFieldsEdit)
		,
		$module['table']['name'],
		"WHERE ".$module['table']['id']."='".$id."'"
		." AND ".$module['table']['status']."!='D'"
	);
	
	if($_GESTOR['database-result']){
		$name = (isset($return_db['name']) ? $return_db['name'] : '');
		$field = (isset($return_db['field']) ? $return_db['field'] : '');

		// ===== Change other variables.

		$_GESTOR['page'] = model_var_replace_all($_GESTOR['page'],'#name#',$name);
		$_GESTOR['page'] = model_var_replace_all($_GESTOR['page'],'#field#',$field);
		$_GESTOR['page'] = model_var_replace_all($_GESTOR['page'],'#id#',$id);
		
		// ===== Populate metaData
		
		$status_current = (isset($return_db[$module['table']['status']]) ? $return_db[$module['table']['status']] : '');
		
		if(isset($return_db[$module['table']['creation_date']])){ $metaData[] = Array('title' => manager_variables(Array('module' => 'interface','id' => 'field-date-start')),'data' => interface_format_data(Array('data' => $return_db[$module['table']['creation_date']], 'format' => 'dateTime'))); }
		if(isset($return_db[$module['table']['modification_date']])){ $metaData[] = Array('title' => manager_variables(Array('module' => 'interface','id' => 'field-date-modification')),'data' => interface_format_data(Array('data' => $return_db[$module['table']['modification_date']], 'format' => 'dateTime'))); }
		if(isset($return_db[$module['table']['version']])){ $metaData[] = Array('title' => manager_variables(Array('module' => 'interface','id' => 'field-version')),'data' => $return_db[$module['table']['version']]); }
		if(isset($return_db[$module['table']['status']])){ $metaData[] = Array('title' => manager_variables(Array('module' => 'interface','id' => 'field-status')),'data' => ($return_db[$module['table']['status']] == 'A' ? '<div class="ui center aligned green message"><b>'.manager_variables(Array('module' => 'interface','id' => 'field-status-active')).'</b></div>' : '').($return_db[$module['table']['status']] == 'I' ? '<div class="ui center aligned brown message"><b>'.manager_variables(Array('module' => 'interface','id' => 'field-status-inactive')).'</b></div>' : '')); }
	} else {
		manager_redirect_root();
	}
	
	// ===== Interface edit finish options
	
	$_GESTOR['interface']['edit']['finish'] = Array(
		'id' => $id, // Identifier of the record being edited.
		'metaData' => $metaData, // Metadata to assemble modification history.
		'database' => Array( // Database definitions of current table.
			'name' => $module['table']['name'], // Table name.
			'id' => $module['table']['id'], // Table reference id.
			'status' => $module['table']['status'], // Table status.
		),
		'buttons' => Array( // Options that appear in the top main menu for navigation inside a module.
			'add' => Array( // Option Id.
				'url' => $_GESTOR['root-url'].$_GESTOR['module-id'].'/add/', // Option URL. In this case: `module-id/add/`
				'label' => manager_variables(Array('module' => 'interface','id' => 'label-button-insert')), // Label that appears on button.
				'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-insert')), // Label that appears as tooltip.
				'icon' => 'plus circle', // Icon used on button: https://fomantic-ui.com/elements/icon.html
				'color' => 'blue', // Button icon color. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
			),
			'status' => Array(
				'url' => $_GESTOR['root-url'].$_GESTOR['module-id'].'/?option=status&'.$module['table']['status'].'='.($status_current == 'A' ? 'I' : 'A' ).'&'.$module['table']['id'].'='.$id.'&redirect='.urlencode($_GESTOR['module-id'].'/edit/?'.$module['table']['id'].'='.$id), // URL formed dynamically according to current status.
				'label' => ($status_current == 'A' ? manager_variables(Array('module' => 'interface','id' => 'label-button-desactive')) : manager_variables(Array('module' => 'interface','id' => 'label-button-active')) ),
				'tooltip' => ($status_current == 'A' ? manager_variables(Array('module' => 'interface','id' => 'tooltip-button-desactive')) : manager_variables(Array('module' => 'interface','id' => 'tooltip-button-active'))),
				'icon' => ($status_current == 'A' ? 'eye' : 'eye slash' ),
				'color' => ($status_current == 'A' ? 'green' : 'brown' ),
			),
			'delete' => Array(
				'url' => $_GESTOR['root-url'].$_GESTOR['module-id'].'/?option=delete&'.$module['table']['id'].'='.$id,
				'label' => manager_variables(Array('module' => 'interface','id' => 'label-button-delete')),
				'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-delete')),
				'icon' => 'trash alternate',
				'color' => 'red',
			),
		),
		'form' => Array( // Form control options
			'validation' => Array( // Form fields validation options.
				Array(
					'rule' => 'text-mandatory', // Standard rule applied to the field.
					'field' => 'name', // Field name in the form.
					'label' => manager_variables(Array('module' => $_GESTOR['module-id'],'id' => 'form-name-label')), // Field label in the form.
					'identifier' => 'name', // Form field identifier.
				)
			),
            'fields' => Array( // Dynamic form fields assembly like select. Example: select of module records.
				Array(
					'type' => 'select', // Field type.
					'id' => 'module', // Field identifier.
					'name' => 'module', // Form field name.
					'search' => true, // Activate search in field.
					'clean' => true, // Option to clean select.
					'selectClass' => 'class', // CSS class of select.
					'placeholder' => manager_variables(Array('restart' => $_GESTOR['module-id'],'module' => $_GESTOR['module-id'],'id' => 'form-module-placeholder')), // Select label.
					'table' => Array( // Table where data will be searched.
						'name' => 'modules', // Table name.
						'field' => 'name', // Field of records that will be placed in select options.
						'numeric_id' => 'id', // Record reference.
						'selected_id' => $module_id, // Current reference in database for selected value.
						'where' => "module_group_id!='libraries'", // Condition to filter records.
					),
				),
			)
		)
	);
}

function module_id_standard_interfaces(){
	global $_GESTOR;
	
	$module = $_GESTOR['module#'.$_GESTOR['module-id']];
	
	switch($_GESTOR['option']){
		case 'list':
			$_GESTOR['interface'][$_GESTOR['option']]['finish'] = Array(
				'database' => Array( // Mapping with all database fields that appear in HTML table with list of all records from database table.
					'name' => $module['table']['name'], // Database table name.
					'fields' => Array(
						'field', // Database table field that appears
					),
					'id' => $module['table']['id'], // `id` field name
					'status' => $module['table']['status'], // `status` field name
				),
				'table' => Array( // HTML Table rules that will be assembled with all rules of each database table field defined above.
					'footer' => true, // Whether or not pagination appears in HTML table footer.
					'columns' => Array(
						Array( // Example of `name` field from database table, which appears in HTML table.
							'id' => 'name', // Database table field defined above in database.fields | database.id | database.status.
							'name' => manager_variables(Array('module' => 'interface','id' => 'field-name')), // Label that appears visually in HTML table header
							'order' => 'asc', // Permission for ASC | DESC sorting
						),
						Array( // Example of `module` field from database table, which has id == `module-id` that will search in another table the value of this id and swap for name == `Module`.
							'id' => 'module', // Database table field defined above in database.fields | database.id | database.status.
							'name' => manager_variables(Array('module' => 'modules','id' => 'module-name')), // Label that appears visually in HTML table header
							'format' => Array( // Formatting option, with formatting id and other necessary data for formatting in some cases.
								'id' => 'otherTable', // Formatting Id.
								'value_if_not_exists' => '<span class="ui info text">N/A</span>', // Default value if not exists in otherTable.
								'table' => Array( // Definition of other table data.
									'name' => 'modules', // Table name.
									'field_swap' => 'name', // Field that will be used the value to put in place of original value. In this case id => name.
									'field_reference' => 'id', // Field that will be used to compare with original table value. In this case `module`.
								),
							)
						),
						Array(
							'id' => $module['table']['modification_date'], // Database table field defined above in database.fields | database.id | database.status.
							'name' => manager_variables(Array('module' => 'interface','id' => 'field-date-modification')), // Label that appears visually in HTML table header
							'format' => 'dateTime', // Formatting option, in this case as there is no other data, it is the formatting id directly.
							'no_search' => true, // This field will not be considered in search module that is in HTML table. 
						),
					),
				),
				'options' => Array( // Options that appear in action list of each record. With small buttons, in last column of HTML table, in header appears written `Options`. Record references are generated automatically using database.id above.
					'edit' => Array( // Option Id.
						'url' => 'edit/', // Option URL. In this case: `module-id/edit/`
						'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-edit')), // Label that appears as tooltip.
						'icon' => 'edit', // Icon used on button: https://fomantic-ui.com/elements/icon.html
						'color' => 'basic blue', // Button icon color. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
					),
					'activate' => Array(
						'option' => 'status',
						'status_current' => 'I',
						'status_change' => 'A',
						'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-active')),
						'icon' => 'eye slash',
						'color' => 'basic brown',
					),
					'deactivate' => Array(
						'option' => 'status',
						'status_current' => 'A',
						'status_change' => 'I',
						'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-desactive')),
						'icon' => 'eye',
						'color' => 'basic green',
					),
					'delete' => Array(
						'option' => 'delete',
						'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-delete')),
						'icon' => 'trash alternate',
						'color' => 'basic red',
					),
				),
				'buttons' => Array( // Options that appear in top main menu for navigation inside a module. In this case there is only button to add a new record.
					'add' => Array( // Option Id.
						'url' => 'add/', // Option URL. In this case: `module-id/add/`
						'label' => manager_variables(Array('module' => 'interface','id' => 'label-button-insert')), // Label that appears on button.
						'tooltip' => manager_variables(Array('module' => 'interface','id' => 'tooltip-button-insert')), // Label that appears as tooltip.
						'icon' => 'plus circle', // Icon used on button: https://fomantic-ui.com/elements/icon.html
						'color' => 'blue', // Button icon color. https://fomantic-ui.com/elements/icon.html#colored | https://fomantic-ui.com/elements/icon.html#inverted
					),
				),
			);
		break;
	}
}


// ==== Ajax

function module_id_ajax_option(){
    global $_GESTOR;

    // ===== Received Parameters

    if(isset($_REQUEST['params'])){ $params = $_REQUEST['params']; } else { $params = []; } // Custom parameters received.

    // ===== Logic

    // ===== Return Data
    
    $_GESTOR['ajax-json'] = [ // These data are then returned in JSON format automatically by manager.php
        'status' => 'ok', // ok | error
    ];
}

// ==== Start

function module_id_start(){
	global $_GESTOR;

    // Always include this function. It will automatically load all libraries defined in configuration file `module_id.json`: "libraries": ["library_name"]. Besides that, automatically manager will include following libraries by default: ['database','manager','model'] . This library name is an alias for library itself which is in `library_name` => `manager/libraries/library_name.php`. All available libraries are in variable: $_GESTOR['libraries-data']. Case not find a library and need a new one, just include logic in a `file.php`, store in folder `manager/libraries/file.php` and include reference of this variable in `manager/config.php` in variable $_GESTOR['libraries-data']. Then, include reference in `module_id.json`: "libraries": ["library_name"]. Which will be loaded here.
	manager_include_libraries();
	
	if($_GESTOR['ajax']){ // This variable is controlled automatically by `manager/manager.php`. When making an AJAX request, this variable will be marked as true and will enter here.
		interface_ajax_start(); // Just include initial operations of interface module for opening AJAX logic.
		
		switch($_GESTOR['ajax-option']){  // This variable is controlled automatically by `manager/manager.php`. Option defined in AJAX request.
			case 'option': module_id_ajax_option(); break; // Access URL will always be same as main interface option that called it. URL is calculated automatically. What changes is 'ajax-option'.
		}
		
		interface_ajax_finish(); // Just include initial operations of interface module for finishing AJAX logic.
	} else {
		module_id_standard_interfaces(); // Standards that change interface module.

		interface_start(); // Just include initial operations of interface module for opening main interface logic.
		
		switch($_GESTOR['option']){
			case 'add': module_id_add(); break; // Reference standard to add a new record to module database.
			case 'edit': module_id_edit(); break; // Reference standard to edit a record of module database.
			case 'option': module_id_option(); break; // Any other option.
		}
		
		interface_finish(); // Just include initial operations of interface module for finishing main interface logic.
	}
}

module_id_start(); // Start module automatically when manager.php identifies a page that references this module.