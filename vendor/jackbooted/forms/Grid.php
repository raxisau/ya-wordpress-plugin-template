<?php

namespace Jackbooted\Forms;

use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

/**
 *
 */
class Grid extends CRUD {

    public static function factory( $gridQuery, $extraArgs = [] ) {
        return new Grid( $gridQuery, $extraArgs );
    }

    private $countSql = '';
    private $gridQuery = '';

    public function __construct( $query, $extraArgs = [] ) {
        $this->gridQuery = $query;
        $this->countSql = ( isset( $extraArgs['countSql'] ) && $extraArgs['countSql'] != false ) ? $extraArgs['countSql'] : 'SELECT COUNT(*) FROM (' . $query . ') AS TMP' . time();

        $props = array_merge( $extraArgs, [ 'canDelete' => false,
            'canUpdate' => false,
            'canInsert' => false ] );
        if ( isset( $extraArgs['tableName'] ) ) {
            $tableName = $extraArgs['tableName'];
            unset( $extraArgs['tableName'] );
        }
        else if ( preg_match( '/^.*from\s+([^\s]+).*/i', $query, $matches ) ) {
            $tableName = $matches[1];
        }
        else {
            echo 'Unable to determine the Table name from query.';
            exit;
        }
        parent::__construct( $tableName, $props );
    }

    protected function createSQLResult() {
        $qry = $this->paginator->getLimits( $this->dbType, $this->gridQuery .
                $this->columnator->getSort() );
        return $this->query( $qry );
    }

    protected function getRowCount() {
        return DB::oneValue( $this->db, $this->countSql );
    }

    protected function getTableMetaData() {
        $parentMeta = parent::getTableMetaData();

        $tab = new DBTable( $this->db, $this->gridQuery . ' LIMIT 1', null, DB::FETCH_ASSOC );
        if ( !$tab->ok() ) {
            return false;
        }

        foreach ( array_keys( $tab->getRow( 0 ) ) as $col ) {
            if ( !isset( $this->columnTitles[$col] ) ) {
                $this->columnTitles[$col] = $this->convertColumnToTitle( $col );
                $this->cellAttributes[$col] = [];
            }
        }

        $displayColumns = array_keys( $tab->getRow( 0 ) );
        $columnNames = array_keys( $this->columnTitles );

        foreach ( $columnNames as $columnName ) {
            if ( !in_array( $columnName, $displayColumns ) ) {
                unset( $this->columnTitles[$columnName] );
                unset( $this->cellAttributes[$columnName] );
                unset( $this->displayType[$columnName] );
            }
        }
        return true;
    }

}
