<?php
/*
 * Copyright 2005-2013 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 * 
 * This program is free software; you can redistribute it and/or modify it under 
 * the terms of the GNU General Public License as published by the Free Software 
 * Foundation ; either version 2 of the License.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A 
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with 
 * this program; if not, see <http://www.gnu.org/licenses>.
 * 
 * Linking this program statically or dynamically with other modules is making a 
 * combined work based on this program. Thus, the terms and conditions of the GNU 
 * General Public License cover the whole combination.
 * 
 * As a special exception, the copyright holders of this program give Centreon 
 * permission to link this program with independent modules to produce an executable, 
 * regardless of the license terms of these independent modules, and to copy and 
 * distribute the resulting executable under terms of Centreon choice, provided that 
 * Centreon also meet, for each linked independent module, the terms  and conditions 
 * of the license of that module. An independent module is a module which is not 
 * derived from this program. If you modify this program, you may extend this 
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 * 
 * For more information : contact@centreon.com
 * 
 * 
 */

# Make broker configuration easier
if (isset($pearDB)) {

    # Add temporary and retention path to general tab
    $query = "ALTER TABLE cfg_centreonbroker
        ADD COLUMN retention_path varchar(255),
        ADD COLUMN stats_activate enum('0','1') DEFAULT '1',
        ADD COLUMN correlation_activate enum('0','1') DEFAULT '0',
        ADD COLUMN buffering_timeout varchar(255) NOT NULL,
        ADD COLUMN retry_interval varchar(255) NOT NULL";
    $pearDB->query($query);

    # Fill retention path
    $query1 = "SELECT config_id
        FROM cfg_centreonbroker";
    $res1 = $pearDB->query($query);
    while ($row1 = $res1->fetchRow()) {
        $retention_path = '/var/lib/centreon-broker/';
        $query2 = "SELECT config_value
            FROM cfg_centreonbroker_info
            WHERE config_key = 'path'
            ORDER BY config_group DESC";
        $res2 = $pearDB->query($query2);
        while ($row2 = $res2->fetchRow()) {
            if (trim($row2['config_value']) != '') {
                $retention_path = dirname(trim($row2['config_value']));
                continue;
            }
        }
        $query3 = "INSERT INTO cfg_centreonbroker (retention_path)
            VALUES ('" . $pearDB->escape($retention_path). "')
            WHERE config_id = " . $pearDB->escape($row1['config_id']);
        $pearDB->query($query3);
    }

    # Delete old temporary configuration
    $query = "DELETE FROM cfg_centreonbroker_info
        WHERE config_group='temporary'";
    $pearDB->query($query);

    # Delete old failover output
    $query = "DELETE FROM cfg_centreonbroker_info
        WHERE (config_id,config_group,config_group_id) IN
            (SELECT config_id,config_group,config_group_id FROM
                (SELECT cbi2.config_id,cbi2.config_group,cbi2.config_group_id 
                FROM cfg_centreonbroker_info cbi1, cfg_centreonbroker_info cbi2, cfg_centreonbroker_info cbi3
                WHERE cbi1.config_id = cbi2.config_id and cbi1.config_group = cbi2.config_group
                AND cbi2.config_id = cbi3.config_id AND cbi2.config_group = cbi3.config_group AND cbi2.config_group_id = cbi3.config_group_id
                AND cbi1.config_group='output'
                AND cbi2.config_group='output'
                AND cbi3.config_group='output'
                AND cbi1.config_key='failover'
                AND cbi2.config_key='name'
                AND cbi1.config_value = cbi2.config_value
                AND cbi3.config_key='type'
                AND cbi3.config_value='file'
                ) as q
            )";
    $pearDB->query($query);

    # Delete failover names which join to non existing failover
    $query ="UPDATE cfg_centreonbroker_info
        SET config_value=''
        WHERE config_key = 'failover'
        AND config_value NOT IN
            (SELECT config_value FROM
                (SELECT config_value
                FROM cfg_centreonbroker_info
                WHERE config_key = 'name'
                ) as q
            )";
    $pearDB->query($query);

    # Enable correlation if it was configured
    $query = "UPDATE cfg_centreonbroker
        SET enable_correlation='1'
        WHERE config_id IN
            (SELECT distinct config_id
            FROM cfg_centreonbroker_info
            WHERE config_group='correlation'
            )";
    $pearDB->query($query);

    # Delete correlation, stats and temporary configuration if it was configured
    $query = "DELETE FROM cfg_centreonbroker_info
        WHERE config_group='correlation'
        OR config_group='stats'
        OR config_group='temporary'
        ");
     $pearDB->query($query);

    # Delete correlation, stats and temporary tabs
    $query = "DELETE FROM cb_tag
        WHERE tagname='correlation'
        OR tagname='stats'
        OR tagname='temporary'";
    $pearDB->query($query);

    # Delete correlation, stats and temporary parameters
    $query = "DELETE FROM cb_module
        WHERE name='correlation'
        OR name='stats'
        OR name='temporary'";
    $pearDB->query($query);

    # Delete buffering_timeout and retry_interval field relations
    $query = "DELETE FROM cb_type_field_relation 
        WHERE cb_field_id IN 
            (SELECT cb_field_id
            FROM cb_field
            WHERE fieldname IN ('buffering_timeout','retry_interval')
            )";
    $pearDB->query($query);
}
?>
