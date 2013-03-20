<?php

define('ROOT', '../private_html/');

require_once ROOT . 'config.php';
require_once ROOT . 'includes/database.php';
require_once ROOT . 'includes/func.php';

// Load all of the countries we can use
$baseEpoch = normalizeTime();
$minimum = strtotime('-30 minutes', $baseEpoch);

function doGeneration($pluginId, $data) {
    global $baseEpoch;
    $plugin = loadPluginByID($pluginId);
    $sum = $data['Sum'];
    $version = $data['MinecraftVersion'];
    $count = $data['Count'];
    $avg = $data['Avg'];
    $max = $data['Max'];
    $min = $data['Min'];
    $variance = $data['Variance'];
    $stddev = $data['StdDev'];

    if ($count == 0 || $sum == 0) {
        return;
    }

    $graph = $plugin->getOrCreateGraph('Game Version', false, 1, GraphType::Pie, true, 9001, true);
    insertGraphDataScratch($graph, $pluginId, $version, $baseEpoch, $sum, $count, $avg, $max, $min, $variance, $stddev);
}

// Plugins
$statement = get_slave_db_handle()->prepare('
        SELECT
            Plugin,
            MinecraftVersion,
            SUM(1) AS Sum,
            COUNT(*) AS Count,
            AVG(1) AS Avg,
            MAX(1) AS Max,
            MIN(1) AS Min,
            VAR_SAMP(1) AS Variance,
            STDDEV_SAMP(1) AS StdDev
        FROM ServerPlugin
        LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server
        WHERE Updated >= ?
        GROUP BY Plugin, MinecraftVersion');
$statement->execute(array($minimum));

while ($row = $statement->fetch()) {
    doGeneration($row['Plugin'], $row);
}

// global plugin
$statement = get_slave_db_handle()->prepare('
        SELECT
            MinecraftVersion,
            SUM(1) AS Sum,
            COUNT(dev.Server) AS Count,
            AVG(1) AS Avg,
            MAX(1) AS Max,
            MIN(1) AS Min,
            VAR_SAMP(1) AS Variance,
            STDDEV_SAMP(1) AS StdDev
        FROM (
          SELECT DISTINCT Server, Server.Players, Server.MinecraftVersion AS MinecraftVersion
          FROM ServerPlugin
          LEFT OUTER JOIN Server ON Server.ID = ServerPlugin.Server
          WHERE ServerPlugin.Updated >= ?
        ) dev
        GROUP BY MinecraftVersion');
$statement->execute(array($minimum));

while ($row = $statement->fetch()) {
    doGeneration(GLOBAL_PLUGIN_ID, $row);
}