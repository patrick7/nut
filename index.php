<?php
/**
 * Show UPS Status in two tables, system and energy.
 *
 * @name      upsstats.php
 * @version   0.99.1
 * @license   GPL v3 (see enclosed license.txt or <http://www.gnu.org/licenses/>)
 * @copyright DO NOT remove @author or @license or @copyright.
 *            This program is free software: you can redistribute it and/or modify
 *            it under the terms of the GNU General Public License as published by
 *            the Free Software Foundation, either version 3 of the License,
 *            or (at your option) any later version.
 *
 *            This program is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *            GNU General Public License for more details.
 *
 *            You should have received a copy of the GNU General Public License
 *            along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @author    patrick velder
 *
 */

/* Include configuration */
if(file_exists("index.php")) {
	include("config.php");
} else {
	die("Error: Config does not exist");
}

/* Define array */
$ups = array();

/* Fetch data from socket */
$fp = fsockopen($config['server'], $config['port'], $errno, $errstr, 30);
if (!$fp) {
	echo "$errstr ($errno)<br />\n";
} else {
	fwrite($fp, "LIST VAR ups\nLOGOUT\n");
	while (!feof($fp)) {
		$line = trim(fgets($fp, 128));
		if(substr($line, 0, 2) == 'OK' ) {
			break;
		}

		/* Cut VAR ups */
		$line = str_replace('VAR ups ', '', $line);

		/* Write ups data to array... */
		$upsdata 	= explode(" ", $line, 2);
		$upsvar 	= trim(str_replace('"','',$upsdata[0]));
		$upsvalue 	= trim(str_replace('"','',$upsdata[1]));
		$ups[$upsvar] 	= $upsvalue;
	}
}
fclose($fp);
?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="css/own.css" rel="stylesheet">
		<link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="bootstrap/css/docs.min.css" rel="stylesheet">
		<style type="text/css">
			body {
				padding-top: 60px;
				padding-bottom: 40px;
			}
		</style>
		<title><?php echo $config['title']; ?></title>
	</head>
	<body>
		<nav class="navbar navbar-fixed-top navbar-inverse" role="navigation">
			<div class="container">
				<div class="navbar-brand"><?= $config['title']; ?></div>
				<p class="navbar-text"><?= $config['version'] ?></p>
			</div>
		</nav>
		<div class="hero-unit">
			<div class="container">
				<h3><? echo $ups['device.mfr'] . ' - ' . $ups['device.model']; ?></h3>
				<br>
				<h4>Ger&auml;t</h4>
				<table style="width:80%" class="table table-striped">
					<?php if(isset($ups['device.serial'])) { ?>
					<tr>
						<td>Seriennummer</td>
						<td><?= $ups['device.serial'] ?></td>
					</tr>
					<?php } if(isset($ups['battery.mfr.date'])) { ?>
					<tr>
						<td>Produktionsdatum Batterie</td>
						<td><?= date('d.m.Y', strtotime($ups['battery.mfr.date'])) ?></td>
					</tr>
					<?php } if(isset($ups['ups.mfr.date'])) { ?>
					<tr>
						<td>Produktionsdatum USV</td>
						<td><?= date('d.m.Y', strtotime($ups['ups.mfr.date'])) ?></td>
					</tr>
					<?php } if(isset($ups['ups.realpower.nominal'])) { ?>
					<tr>
						<td>Leistung</td>
						<td><?= $ups['ups.realpower.nominal'] ?> Watt</td>
					</tr>
					<?php } ?>
				</table>
				<br>
				<h4>Energie</h4>
				<table style="width:80%" class="table table-striped">
					<?php if(isset($ups['ups.status'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-off" aria-hidden="true"></span> Status</td>
						<td>
						<?php
						switch($ups['ups.status']) {
							case 'OL':
								echo '<span class="label label-success">Online</span>';
								break;;
							case 'OB DISCHRG':
								echo '<span class="label label-danger">Batterie</span>';
								break;;
							case 'OL CHRG':
								echo '<span class="label label-warning">Laden</span>';
								break;;
							case 'OL CHRG LB':
								echo '<span class="label label-warning">Laden (Batterie fast leer)</span>';
								break;;
							default:
								echo '<spann class="label label-info">Unknown</span>';
						}
						?></td>
					</tr>
					<?php } if(isset($ups['battery.charge'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Ladestatus</td>
						<td>
							<div class="progress">
								<?php
									if($ups['battery.charge'] < 26) {
										$charged_status = "danger";
									} elseif ($ups['battery.charge'] < 50) {
									$charged_status = "warning";
										} elseif ($ups['battery.charge'] < 75) {
										$charged_status = "info";
									} elseif ($ups['battery.charge'] == 100) {
										$charged_status = "success";
									}
								?>
								<div class="progress-bar progress-bar-striped progress-bar-<?php echo $charged_status; ?>" role="progressbar" aria-valuenow="<?php echo $ups['battery.charge']; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo round($ups['battery.charge'],0); ?>%;"><?php echo round($ups['battery.charge'],0); ?>% geladen</div>
							</div>
						</td>
					</tr>
					<?php } if(isset($ups["ups.realpower.nominal"]) && isset($ups["ups.load"])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-stats" aria-hidden="true"></span> Auslastung</td>
						<td>
							<div class="progress">
								<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="<?php echo round($ups["ups.realpower.nominal"] * $ups["ups.load"] / 100,0); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo round($ups["ups.load"],0); ?>%;"><?php echo round($ups["ups.realpower.nominal"] * $ups["ups.load"] / 100, 2); ?>W - <?php echo round($ups["ups.load"],0); ?>%</div>
							</div>
						</td>
					</tr>
					<?php } if(isset($ups['battery.runtime'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-time" aria-hidden="true"></span> Laufzeit</td>
						<td><? echo round($ups['battery.runtime']/60,2); ?> min</td>
					</tr>
					<?php } if(isset($ups['input.voltage'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Eingangsspannung</td>
						<td><? echo $ups['input.voltage']; ?> Volt</td>
					</tr>
					<?php } if(isset($ups['output.voltage']) && isset($ups['input.voltage'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-minus" aria-hidden="true"></span> Ausgangsspannung</td>
						<td><? echo $ups['output.voltage']; ?> Volt (<?php if($ups['output.voltage']-$ups['input.voltage'] > 0) { echo "+"; } echo $ups['output.voltage']-$ups['input.voltage']; ?>V)</td>
					</tr>
					<?php } if(isset($ups['battery.voltage'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-signal" aria-hidden="true"></span> Batteriespannung</td>
						<td><? echo $ups['battery.voltage']; ?> Volt</td>
					</tr>
					<?php } if(isset($ups['battery.temperature'])) { ?>
					<tr>
						<td><span class="glyphicon glyphicon-fire" aria-hidden="true"></span> Batterietemperatur</td>
						<td><? echo $ups['battery.temperature']; ?> °C</td>
					</tr>
					<?php } ?>
				</table>
<?php
if(isset($config['debug']) && $config['debug'] == 'true') {
	echo '<div class="debug">';
        echo '<pre>';
        var_dump($ups);
        echo '</pre>';
	echo '</div>';
}
?>
			</div>
		<br />
		</div>
		<div class="footer">
			<p>&copy; <?php echo date('Y'); ?> by <?php echo $config['copyright']; ?></p>
		</div>
	</body>
</html>
