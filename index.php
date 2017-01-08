<?php
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<meta http-equiv="X-Frame-Options" content="deny">
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/header.php');
	?> 
	<!-- This is inner text! -->
	<div  id="innertext">
		<h1></h1>

		<div id="quicksearchdiv">
			<div style="float:left;">
				<?php
				//---------------------------QUICK SEARCH SETTINGS---------------------------------------
				//Title text that will appear. 
				$searchText = 'Search Taxon'; 
		
				//Text that will appear on search button. 
				$buttonText = 'Search';
		
				//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
				include_once($SERVER_ROOT.'/classes/PluginsManager.php');
				$pluginManager = new PluginsManager();
				$quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
				echo $quicksearch;
				?>
			</div>
		</div>
		<div style="padding: 0px 15px;">
			<h1>Welcome to the University of California, Santa Barbara Natural History Data Portal</h1>
			<h2><div style="margin:50px 130px 130px 150px;">
			The SCC data portal was created to serve as a gateway to distributed data resources within the University of California Santa Barbara and UCSB Natural Reserve System and UCSB Center for Biodiversity and Ecological Restoration. Through a common web interface, we offer tools to locate, access and work with a variety of data. SCC is more than just a web site - it is a suite of data access technologies and a distributed network of collections across UCSB, or that have holdings from UCSB and UCSB NRS locations.
			</div><h2>
		</div>
	</div>

	<?php
	include($SERVER_ROOT.'/footer.php');
	?> 
</body>
</html>