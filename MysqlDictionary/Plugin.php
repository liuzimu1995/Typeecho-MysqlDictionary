<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * MysqlDictionary 数据库字典
 *
 * @package MysqlDictionary
 * @author liuzimu
 * @version 1.0.0
 * @link http://typecho.org
 */
class MysqlDictionary_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MysqlDictionary_Plugin', 'parse');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render($dbserver, $dbusername, $dbpassword, $database, $DB_PORT, $noshowtable)
    {
        ini_set("mssql.textsize", 200000);
        ini_set("mssql.textlimit", 200000);
        $options = [];
        $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";//为兼容各版本PHP
        $dsn = "mysql:host=$dbserver;dbname=$database;port=$DB_PORT;charset=utf8";
        $PDO = new \PDO($dsn, $dbusername, $dbpassword, $options);

        $sql = "show tables";
        $query = $PDO->query($sql);//查询
        $table_result = $query->fetchAll();

        $no_show_table = array();    //不需要显示的表
        $no_show_field = array();   //不需要显示的字段

        if (!empty($noshowtable)) {
            $no_show_table = explode(',', $noshowtable);
        }

        foreach ($table_result as $k1 => $row) {
            if (!in_array($row[0], $no_show_table)) {
                $tables[]['TABLE_NAME'] = $row[0];
            }
        }
        foreach ($tables as $k => $v) {
            $sql = 'SELECT * FROM ';
            $sql .= 'INFORMATION_SCHEMA.TABLES ';
            $sql .= 'WHERE ';
            $sql .= "table_name = '{$v['TABLE_NAME']}'  AND table_schema = '{$database}'";

            $query = $PDO->query($sql);//查询
            $table_result = $query->fetchAll();
            foreach ($table_result as $k1 => $t) {
                $tables[$k]['TABLE_COMMENT'] = $t['TABLE_COMMENT'];
            }

            $sql = 'SELECT * FROM ';
            $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
            $sql .= 'WHERE ';
            $sql .= "table_name = '{$v['TABLE_NAME']}' AND table_schema = '{$database}'";

            $fields = array();

            $query = $PDO->query($sql);//查询
            $field_result = $query->fetchAll();
            foreach ($field_result as $k1 => $t) {
                $fields[] = $t;
            }

            //测试数据

            $tables[$k]['COLUMN'] = $fields;
        }

        $PDO = null;

        $html = '';
        /**因为自带菜单，这里注释掉**/
//        $html .= "<button onclick='showMenu();'>展示菜单</button><div id='menuId' style='display: none;'>";
//        foreach ($tables as $k => $v) {
//            if (in_array($v['TABLE_NAME'], $no_show_field)) {
//                continue;  //不显示
//            }
//            $html .= ($k + 1) . "、<a id='LINK_{$v['TABLE_NAME']}' href='#{$v['TABLE_NAME']}'>" . "{$v['TABLE_NAME']}</a>" . "{$v['TABLE_COMMENT']}\n";
//            $html .= '<br>';
//        }
//        $html .= "</div>";
        /**因为自带菜单，这里注释掉**/
        foreach ($tables as $k => $v) {
            if (in_array($v['TABLE_NAME'], $no_show_field)) {
                continue;  //不显示
            }

            $html .= '  <h3 id="' . $v['TABLE_NAME'] . '">' . ($k + 1) . '、' . $v['TABLE_COMMENT'] . '  （' . $v['TABLE_NAME'] . '）</h3>' . "\n";

            $html .= '  <table border="1" cellspacing="0" cellpadding="0" width="100%">' . "\n";
            $html .= '      <tbody>' . "\n";
            $html .= '          <tr>' . "\n";
            $html .= '              <th>字段名</th>' . "\n";
            if (isset($_GET['demo']) && $_GET['demo']) {
                $html .= '              <th>测试数据(' . $v['DEMO_COUNT'] . ')</th>' . "\n";
            }
            $html .= '              <th>数据类型</th>' . "\n";
            $html .= '              <th>默认值</th>' . "\n";
            $html .= '              <th>允许非空</th>' . "\n";
            $html .= '              <th>自动递增</th>' . "\n";
            $html .= '              <th>备注</th>' . "\n";
            $html .= '          </tr>' . "\n";

            foreach ($v['COLUMN'] as $f) {
                if (isset($no_show_field[$v['TABLE_NAME']]) == false || is_array($no_show_field[$v['TABLE_NAME']]) == false) {
                    $no_show_field[$v['TABLE_NAME']] = array();
                }
                if (!in_array($f['COLUMN_NAME'], $no_show_field[$v['TABLE_NAME']])) {
                    $html .= '          <tr>' . "\n";
                    $html .= '              <td class="c1">' . $f['COLUMN_NAME'] . '</td>' . "\n";
                    if (isset($_GET['demo']) && $_GET['demo']) {
                        $html .= '              <td class="c1">' . implode('<br>', $f['DEMO_LIST']) . '</td>' . "\n";
                    }
                    $html .= '              <td class="c2">' . $f['COLUMN_TYPE'] . '</td>' . "\n";
                    $html .= '              <td class="c3">' . $f['COLUMN_DEFAULT'] . '</td>' . "\n";
                    $html .= '              <td class="c4">' . $f['IS_NULLABLE'] . '</td>' . "\n";
                    $html .= '              <td class="c5">' . ($f['EXTRA'] == 'auto_increment' ? '是' : '&nbsp;') . '</td>' . "\n";
                    $html .= '              <td class="c6">' . $f['COLUMN_COMMENT'] . '</td>' . "\n";
                    $html .= '          </tr>' . "\n";
                }
            }
            $html .= '      </tbody>' . "\n";
            $html .= '  </table>' . "\n";
        }

        $tpl = <<<EOF
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>数据库字典</title>
    <meta name="generator" content="ThinkDb V1.0" />
    <meta name="author" content="author" />
    <meta name="copyright" content="copyright" />
    <style>
        body, td, th { font-family: "微软雅黑"; font-size: 14px; }
        .warp{margin:auto; width:900px;}
        .warp h3{margin:0px; padding:0px; line-height:30px; margin-top:10px;}
        table { border-collapse: collapse; border: 1px solid #CCC; }
        table th { text-align: left; font-weight: bold; height: 26px; line-height: 26px; font-size: 14px; text-align:center; border: 1px solid #CCC; padding:5px;}
        table td { height: 20px; font-size: 14px; border: 1px solid #CCC; padding:5px;}
        .c1 { width: 120px; }
        .c2 { width: 120px; }
        .c3 { width: 50px; }
        .c4 { width: 50px; text-align:center;}
        .c5 { width: 50px; text-align:center;}
        .c6 { width: 270px; }
    </style>
</head>
<body>
<div class="warp">
    <h1 style="text-align:center;">{$database}数据库字典</h1>
    <script>
        var _show = 0;
        function showMenu() {
            var id = "menuId";
            _show = _show?0:1;
            document.getElementById(id).style.display = _show?'':'none';
        }
    </script>
    {$html}
</div>
</body>
</html>
EOF;

        return $tpl;
    }


    /**
     * 插件实现方法
     *
     * @access public
     * @return string
     */
    public static function parse($html, $widget, $lastResult)
    {
//        print_r($html);die;
        $html = empty($lastResult) ? $html : $lastResult;
        // <!--mysql-dictionary-->
        /** 检查是否有正确配置标签 **/
        if (strpos($html, '<!--mysql-dictionary-->') === false) {
            return $html;
        }
        $host = self::getParams('host', $html);
        if (empty($host)) {
            return $html;
        }
        $username = self::getParams('username', $html);
        if (empty($username)) {
            return $html;
        }
        $password = self::getParams('password', $html);
        if (empty($password)) {
            return $html;
        }
        $database = self::getParams('database', $html);
        if (empty($database)) {
            return $html;
        }
        $port = self::getParams('port', $html);
        if (empty($port)) {
            $port = 3306;
        }
        $noshowtable = self::getParams('noshowtable', $html);
        if (empty($noshowtable)) {
            $noshowtable = '';
        }
//        var_dump($host, $username, $password, $database, $port);die;
        try {
            $render = self::render($host, $username, $password, $database, $port, $noshowtable);
        } catch (\Exception $e) {
            $html = $e->getMessage() . $html;
            return $html;
        }
        /** 检查是否有正确配置标签 **/
        $html = preg_replace([
            '/<!--\s*mysql-dictionary\s*-->/i',
            '/<!--\s*mysql-dictionary-host=.*?-->*/is',
            '/<!--\s*mysql-dictionary-username=.*?-->*/is',
            '/<!--\s*mysql-dictionary-password=.*?-->*/is',
            '/<!--\s*mysql-dictionary-database=.*?-->*/is',
            '/<!--\s*mysql-dictionary-port=.*?-->*/is',
        ], [$render, '', '', '', '', ''], $html);
        return $html;
    }

    /**
     * @param $key
     * @param $data
     * @return mixed|string
     */
    public static function getParams($key, $data)
    {
        $host = [];
        $pattern = '/<!--\s*mysql-dictionary-' . $key . '=.*?-->*/is';
        preg_match($pattern, $data, $host);
        $host = empty($host[0]) ? '' : $host[0];
        if (empty($host)) return $host;
        $host = explode('=', $host);
        $host = empty($host[1]) ? '' : $host[1];
        $host = str_replace('-->', '', $host);
        return htmlentities($host);
    }
}
