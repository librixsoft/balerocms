<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Database\MySQL;

class Model
{
    private MySQL $db;
    private ConfigSettings $configSettings;

    public function __construct(ConfigSettings $configSettings, MySQL $db)
    {
        $this->configSettings = $configSettings;
        $this->db = $db;

        // Solo conecta si la app ya está instalada
        if ($this->configSettings->installed === 'yes') {
            $this->db->connect(
                $this->configSettings->dbhost,
                $this->configSettings->dbuser,
                $this->configSettings->dbpass,
                $this->configSettings->dbname
            );
        }
    }
}
