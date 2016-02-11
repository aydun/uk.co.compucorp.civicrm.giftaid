<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                              |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

class CRM_Civigiftaid_Report_Form_Contribute_GiftAid extends CRM_Report_Form {

    protected $_addressField = false;
    protected $_customGroupExtends = array( 'Contribution' );

    function __construct( ) {
      $this->_columns =
        array(
          'civicrm_entity_batch' => array(
            'dao' => 'CRM_Batch_DAO_EntityBatch',
            'filters' =>
            array(
              'batch_id' => array(
                'title' => 'Batch',
                'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                'options'      => CRM_Civigiftaid_Utils_Contribution::getBatchIdTitle( 'id desc' ),
              ),
            ),
          ),
          'civicrm_contact' =>
            array(
              'dao' => 'CRM_Contact_DAO_Contact',
              'grouping' => 'contact-fields',
              'fields' => array(
                'prefix_id' =>array( 'default' => TRUE ),
                'first_name' =>array( 'default' => TRUE ),
                'last_name' =>array( 'default' => TRUE ),
                'display_name' =>array( 'title' => 'Name of Donor' ),
              ),
            ),
          'civicrm_address' =>
            array(
              'dao' => 'CRM_Core_DAO_Address',
              'grouping' => 'contact-fields',
              'fields' =>
              array(
                'street_address' => array('default' => TRUE),
                'city' => NULL,
                'state_province_id' => array('title' => ts('State/Province'),),
                'country_id' => array('title' => ts('Country'),),
                'postal_code' => array('default' => TRUE),
              ),
            ),
          'civicrm_contribution' =>
            array(
              'dao' => 'CRM_Contribute_DAO_Contribution',
              'fields' => array(
                'contribution_id' => array(
                  'name'       => 'id',
                  'title'      => 'Contribution ID',
                  'no_display' => true,
                  'required'   => true,
                ),
                'contact_id' => array(
                  'name' => 'contact_id',
                  'title'  => 'Contact ID',
                  'no_display' => TRUE,
                  'required'   => true,
                ),
                'receive_date' => array(
                  'name'  => 'receive_date',
                  'title'      => 'Contribution Date',
                  'no_display' => false,
                  'default'   => TRUE,
                ),
              ),
            ),
        );

        $this->_options=array(
          'hmrc_format' => array(
            'title' => ts('Format for HMRC spreadsheet?'),
            'type' => 'checkbox'
          ),
        );

        parent::__construct( );

        // set defaults
        if ( is_array( $this->_columns['civicrm_value_gift_aid_submission'] ) ) {
            foreach ( $this->_columns['civicrm_value_gift_aid_submission']['fields'] as $field => $values ) {
                if ($values['name']  == 'amount') {
	            $this->_columns['civicrm_value_gift_aid_submission']['fields'][$field]['default'] = true;
                }
            }
        }
    }

    function select( ) {
        $select = array( );

        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                        if ( $tableName == 'civicrm_address' ) {
                            $this->_addressField = true;
                        } else if ( $tableName == 'civicrm_email' ) {
                            $this->_emailField = true;
                        }

                        // only include statistics columns if set
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  =
                                        $field['type'];
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'count':
                                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'avg':
                                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type']  =
                                        $field['type'];
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                }
                            }

                        } else {
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                        }
                    }
                }
            }
        }

        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
    }

    function from( ) {
        $this->_from = "
          FROM civicrm_entity_batch {$this->_aliases['civicrm_entity_batch']}
          INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                  ON {$this->_aliases['civicrm_entity_batch']}.entity_table = 'civicrm_contribution' AND
                     {$this->_aliases['civicrm_entity_batch']}.entity_id = {$this->_aliases['civicrm_contribution']}.id

          LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
          ON ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id
             AND {$this->_aliases['civicrm_contact']}.is_deleted = 0 )


          LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
          ON ({$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_address']}.contact_id
             AND {$this->_aliases['civicrm_address']}.is_primary = 1 )";
        }

    function where( ) {
        parent::where( );

        if ( empty($this->_where) ) {
            $this->_where = "WHERE value_gift_aid_submission_civireport.amount IS NOT NULL";
        } else {
            $this->_where .= " AND value_gift_aid_submission_civireport.amount IS NOT NULL";
        }
    }

  function statistics( &$rows ) {
        $statistics = parent::statistics( $rows );

        $select = "
        SELECT SUM( value_gift_aid_submission_civireport.amount ) as amount,
               SUM( value_gift_aid_submission_civireport.gift_aid_amount ) as giftaid_amount";
        $sql = "{$select} {$this->_from} {$this->_where}";
        $dao = CRM_Core_DAO::executeQuery( $sql );

        if ( $dao->fetch( ) ) {
            $statistics['counts']['amount']    = array( 'value' => $dao->amount,
                                                        'title' => 'Total Amount',
                                                        'type'  => CRM_Utils_Type::T_MONEY );
            $statistics['counts']['giftaid']       = array( 'value' => $dao->giftaid_amount,
                                                        'title' => 'Total Gift Aid Amount',
                                                        'type'  => CRM_Utils_Type::T_MONEY );
        }
        //print_r ($config);exit;
        return $statistics;
    }

    function postProcess( ) {
        parent::postProcess( );
    }

    function alterDisplay( &$rows ) {
        // custom code to alter rows
        $entryFound = false;
        $hmrc_format = array_key_exists('hmrc_format', $this->_params) && CRM_Utils_Array::value('hmrc_format', $this->_params);

        $cols_added = FALSE;
        if ($hmrc_format && !$cols_added) {
           // add two blank columns before Contribution Date
           // to make copy/paste from CSV into HMRC spreadsheet easier
           $index = array_search( 'civicrm_contribution_receive_date', array_keys($this->_columnHeaders) );
           $new_cols = array(
             'aggregated' => array(
               'title' => ts('Aggregated Donations'),
             ),
             'sponsored' => array(
               'title' => ts('Sponsored event'),
             ),
           );
           array_splice($this->_columnHeaders, $index, 0, $new_cols);
           $cols_added = TRUE;
        }
        if ( array_key_exists('civicrm_contact_prefix_id', $this->_columnHeaders) ) {
            // we change this from id to string
            $this->_columnHeaders['civicrm_contact_prefix_id']['type'] = 2;
        }
        $donation_key = NULL;
        foreach ( $rows as $rowNum => $row ) {
            if ( array_key_exists('civicrm_contribution_contact_id', $row) && array_key_exists('civicrm_contact_display_name', $row)) {
                if ( $value = $row['civicrm_contribution_contact_id'] ) {
                    $url = CRM_Utils_System::url( "civicrm/contact/view"  ,
                                            'reset=1&cid=' . $value,
                                            $this->_absoluteUrl );
                    $rows[$rowNum]['civicrm_contact_display_name_link' ] = $url;
                    $rows[$rowNum]['civicrm_contact_display_name_hover'] =
                        ts("View Contact Summary for this Contact.");
                }
                $entryFound = true;
            }

            if ( array_key_exists('civicrm_contact_prefix_id', $row) ) {
              if ( $value = $row['civicrm_contact_prefix_id'] ) {
                 $value = CRM_Core_Pseudoconstant::getLabel('CRM_Contact_BAO_Contact', 'prefix_id', $value);
                 if ($hmrc_format) {
                     // Limit prefix fo 4 chars
                     $value = substr( $value, 0, 4 );
                 }
                 $rows[$rowNum]['civicrm_contact_prefix_id'] = $value;
              }
              $entryFound = true;
            }
            if ( array_key_exists('civicrm_address_country_id', $row) ) {
              if ( $value = $row['civicrm_address_country_id'] ) {
                 $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
              }
              $entryFound = true;
            }
            if ( array_key_exists('civicrm_address_state_province_id', $row) ) {
              if ( $value = $row['civicrm_address_state_province_id'] ) {
                 $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
              }
              $entryFound = true;
            }

            if ( $hmrc_format ) {
              // See https://www.gov.uk/guidance/schedule-spreadsheet-to-claim-back-tax-on-gift-aid-donations
              // First name is max 35 chars, no spaces
              $key = 'civicrm_contact_first_name';
              if ( array_key_exists($key, $row) ) {
                 if ( $value = trim($row[$key]) ) {
                     $value = explode(" ", $value, 2)[0];
                     $rows[$rowNum][$key] = substr( $value, 0, 35 );
                 }
              }

              // Last name is max 35 chars, replace hyphens with spaces
              $key = 'civicrm_contact_last_name';
              if ( array_key_exists($key, $row) ) {
                 if ( $value = trim($row[$key]) ) {
                     $value = str_replace('-', ' ', $value);
                     $rows[$rowNum][$key] = substr( $value, 0, 35 );
                 }
              }

              // Only show number if we can extract that
              $key = 'civicrm_address_street_address';
              if ( array_key_exists($key, $row) ) {
                 if ( $value = $row[$key] ) {
                     if (preg_match( '/^(\d+)/', $value, $matches) ) {
                          $rows[$rowNum][$key] = $matches[1];
                     }
                 }
              }

              // Post code should be upper case
              $key = 'civicrm_address_postal_code';
              if ( array_key_exists($key, $row) ) {
                 if ( $value = trim($row[$key]) ) {
                     $rows[$rowNum][$key] = strtoupper($value);
                 }
              }

              // Donation date is DD/MM/YY
              // Format & override type to prevent further changes
              $key = 'civicrm_contribution_receive_date';
              if ( array_key_exists($key, $row) ) {
                 if ( $value = $row[$key] ) {
                     $value = CRM_Utils_Date::customFormat($value, '%d/%m/%Y');
                     // customFormat doesn't understand %y for 2 digit years ...
                     $value = substr( $value, 0, 6 ) . substr( $value, -2 );
                     $rows[$rowNum][$key] = $value;
                     $this->_columnHeaders[$key]['type'] = 2;
                 }
              }

              // Donation amount should not have currency symbol
              // Format & override type to prevent further changes
              if ( !$donation_key ) {
                 // Better way to find donation key ??
                 foreach ($this->_columnHeaders as $k => $v) {
                   if ($v['title'] == 'Amount') {
                      $donation_key = $k;
                   }
                 }
              }
              if ( $donation_key && array_key_exists($donation_key, $row) ) {
                 if ( $value = $row[$donation_key] ) {
                     $rows[$rowNum][$donation_key] = sprintf("%01.2f", $value);
                     $this->_columnHeaders[$donation_key]['type'] = 2;
                 }
              }
              $entryFound = TRUE;
            }

            // skip looking further in rows, if first row itself doesn't
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
            $lastKey = $rowNum;
        }
    }

}
