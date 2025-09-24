<?php

namespace Framework\Static;

class Constant
{
    public const LOCAL_BASEPATH = LOCAL_DIR;
    public const TABLES_SQL_PATH = self::LOCAL_BASEPATH . "/Modules/Installer/sql/tables.sql";
    public const CONFIG_PATH = self::LOCAL_BASEPATH . '/resources/config/balero.config.json';
    //public const VIEWS_PATH = self::LOCAL_BASEPATH . '/resources/views';
    public const LANG_HELPER = self::LOCAL_BASEPATH . '/Framework/I18n/lang_helper.php';
    public const LANG_PATH = self::LOCAL_BASEPATH . '/resources/lang';
    public const UPLOADS_PATH = self::LOCAL_BASEPATH . '/public/assets/images/uploads/'; // Local path
    public const REMOTE_UPLOADS_PATH = '/assets/images/uploads/'; // Remote path

}
