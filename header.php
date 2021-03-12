<SCRIPT>
<!--
if (top.frames.length!=0)
  top.location=self.document.location;
// -->
</SCRIPT>
<table id="maintable" cellspacing="0">
	<tr>
		<td class="header" colspan="3">
			<div style="clear:both;">
				<div style="clear:both;">
					<img style="" src="<?php echo $clientRoot; ?>/images/layout/logo.jpg" />
				</div>
			</div>
			<div id="top_navbar">
				<div id="right_navbarlinks">
					<?php
					if($userDisplayName){
					?>
						<span style="">
							Welcome <?php echo $userDisplayName; ?>!
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a>
						</span>
					<?php
					}
					else{
					?>
						<span style="">
							<a href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
								Log In
							</a>
						</span>
						<span style="margin-left:5px;">
							<a href="<?php echo $clientRoot; ?>/profile/newprofile.php">
								New Account
							</a>
						</span>
					<?php
					}
					?>
					<span style="margin-left:5px;margin-right:5px;">
						<a href='<?php echo $clientRoot; ?>/sitemap.php'>Sitemap</a>
					</span>
					
				</div>
				<ul id="hor_dropdown">
					<li>
						<a href="<?php echo $clientRoot; ?>/index.php" >Home</a>
					</li>
					<li>
						<a href="#" >Search</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/index.php" >Search Collections</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/collections/map/mapinterface.php" target="_blank">Map Search</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="#" >Images</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/imagelib/index.php" >Image Browser</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/imagelib/search.php" >Search Images</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/projects/index.php" >Insect Checklists</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=10" >Insects of UCSB and Nearby</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=25" >Insects of North Campus Open Space</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=6" >Bees of UCSB and Nearby</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=28" >Ichneumonoidea of UCSB and Nearby</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=11" >Ants of Santa Barbara County</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=7" >Ophioninae of Coastal California</a>
							</li>
						</ul>
					</li>
					<li>
						<a href="<?php echo $clientRoot; ?>/projects/index.php" >UCSB Natural Reserve Checklists</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=27" >Santa Cruz Island Reserve Plants</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=24" >Santa Cruz Island Reserve Lichens</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=23" >Santa Cruz Island Reserve Fungi</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=33" >Santa Cruz Island Bees</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=61" >Coal Oil Point Reserve Plant Voucher Checklist</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=53" >Coal Oil Point Reserve Plant Observation Checklist</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=57" >Coal Oil Point Reserve Arthropod Voucher Checklist</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/checklist.php?cl=56" >Coal Oil Point Reserve Arthropod Observation Checklist</a>
							</li>
						</ul>
					</li>
					<!--<li>
						<a href="#" >Interactive Tools</a>
						<ul>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=checklist" >Dynamic Checklist</a>
							</li>
							<li>
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key" >Dynamic Key</a>
							</li>-->
						</ul> 
					</li>
				</ul>
			</div>
		</td>
	</tr>
    <tr>
		<td class='middlecenter'  colspan="3">