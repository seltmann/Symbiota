<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributes.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/editor/extras/attributemining.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$taxonFilter = array_key_exists('taxonfilter',$_POST)?$_POST['taxonfilter']:'';
$tidFilter = array_key_exists('tidfilter',$_POST)?$_POST['tidfilter']:'';
$fieldName = array_key_exists('fieldname',$_POST)?$_POST['fieldname']:'';
$traitID = array_key_exists('traitid',$_POST)?$_POST['traitid']:'';
$submitForm = array_key_exists('submitform',$_POST)?$_POST['submitform']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($tidFilter)) $tidFilter = 0;
if(!is_numeric($traitID)) $traitID = 0;

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		//If a page related to collections, one maight want to... 
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = 1;
		}
	}
}

$attrManager = new OccurrenceAttributes();

$statusStr = '';
if($isEditor){
	if($submitForm == 'Batch Assign State'){
		if($collid && $fieldName){
			$fieldValue = array_key_exists('fieldvalue',$_POST)?$_POST['fieldvalue']:'';
			if(!is_array($fieldValue)) $fieldValue = array($fieldValue);
			$stateID = array_key_exists('stateid',$_POST)?$_POST['stateid']:'';
			$notes = $_POST['notes'];
			if(!is_array($stateID)) $stateID = array($stateID);
			if($stateID && $fieldValue){
				foreach($fieldValue as $fValue){
					foreach($stateID as $id){
						if(!$attrManager->submitBatchAttributes($collid, $id, $fieldName, $fValue, $notes, $SYMB_UID)){
							$statusStr = $attrManager->getErrorMessage();
						}
					}
				}
			}
		}
	}
}

$fieldArr = array('habitat' => 'Habitat', 'substrate' => 'Substrate', 'occurrenceremarks' => 'Occurrence Remarks (notes)',
	'dynamicproperties' => 'Dynamic Properties', 'verbatimattributes' => 'Verbatim Attributes (description)',
	'behavior' => 'Behavior', 'reproductivecondition' => 'Reproductive Condition', 'lifestage' => 'Life Stage', 
	'sex' => 'Sex');
?>
<html>
	<head>
		<title>Occurrence Attribute Mining Tool</title>
		<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#taxonfilter").autocomplete({ 
					source: "rpc/getTaxonFilter.php", 
					dataType: "json",
					minLength: 3,
					select: function( event, ui ) {
						$("#tidfilter").val(ui.item.id);
					}
				});
	
				$("#taxonfilter").change(function(){
					$("#tidfilter").val("");
					if($( this ).val() != ""){
						$( "#filtersubmit" ).prop( "disabled", true );
						$( "#verify-span" ).show();
						$( "#notvalid-span" ).hide();
											
						$.ajax({
							type: "POST",
							url: "rpc/getTaxonFilter.php",
							data: { term: $( this ).val(), exact: 1 }
						}).done(function( msg ) {
							if(msg == ""){
								$( "#notvalid-span" ).show();
							}
							else{
								$("#tidfilter").val(msg[0].id);
							}
							$( "#filtersubmit" ).prop( "disabled", false );
							$( "#verify-span" ).hide();
						});
					}
				});
			});

			function verifyFilterForm(f){
				if(f.traitid.value == ""){
					alert("You must select a trait");
					return false;
				}
				if(f.fieldname.value == ""){
					alert("A target field must be selected");
					return false;
				}
				return true;
			}

			function verifyMiningForm(f){
				if(f.elements["fieldvalue[]"].selectedIndex == -1){
					alert("You muct select at least one field value");
					return false;
				}
				
				var formVerified = false;
				for(var h=0;h<f.length;h++){
					if(f.elements[h].name == "stateid[]" && f.elements[h].checked){
						formVerified = true;
						break;
					}
				}
				if(!formVerified){
					alert("Please choose at least one state to assign");
					return false;
				}
				return true;
			}
		</script>
	</head>
	<body style="width:900px">
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
			<b>Attribute Mining Tool</b>
		</div>
		<?php 
		if($statusStr){
			echo '<div style="color:red">';
			echo $statusStr;
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<?php 
			if($collid){
				?>
				<div style="width:600px;">
					<fieldset style="margin:15px;padding:15px">
						<legend><b>Filter</b></legend>
						<form name="filterform" method="post" action="attributemining.php" onsubmit="return verifyFilterForm(this)" >
							<div>
								<b>Taxon: </b>
								<input id="taxonfilter" name="taxonfilter" type="text" value="<?php echo $taxonFilter; ?>" /> 
								<input id="tidfilter" name="tidfilter" type="hidden" value="<?php echo $tidFilter; ?>" />
								<span id="verify-span" style="display:none;font-weight:bold;color:green;">verifying taxonomy...</span>
								<span id="notvalid-span" style="display:none;font-weight:bold;color:red;">taxon not valid...</span>
							</div>
							<div>
								<select name="traitid">
									<option value="">Select Trait</option>
									<option value="">------------------------------------</option>
									<?php 
									$traitNameArr = $attrManager->getTraitNames();
									if($traitNameArr){
										foreach($traitNameArr as $ID => $aName){
											echo '<option value="'.$ID.'" '.($traitID==$ID?'SELECTED':'').'>'.$aName.'</option>';
										}
									}
									else{
										echo '<option value="0">No attributes are available</option>';
									}
									?>
								</select>
							</div>
							<div>
								<select name="fieldname">
									<option value="">Select Target Field</option>
									<option value="">------------------------------------</option>
									<?php 
									foreach($fieldArr as $k => $fName){
										echo '<option value="'.$k.'" '.($k==$fieldName?'SELECTED':'').'>'.$fName.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input id="filtersubmit" name="submitform" type="submit" value="Get Field Values" />
							</div>
						</form>
					</fieldset>
				</div>
				<?php 
				if($traitID && $fieldName){
					?>
					<div style="width:600px">
						<fieldset style="margin:15px;padding:15px">
							<legend><b><?php echo $fieldArr[$fieldName]; ?></b></legend>
							<form name="miningform" method="post" action="attributemining.php" onsubmit="return verifyMiningForm(this)">
								<div style="margin:5px;">
									<b>Select Target Field Value(s)</b> - hold down control or shift buttons to select more than one value<br/>
									<select name="fieldvalue[]" size="15" multiple="multiple" style="width:100%">
										<?php 
										$valueArr = $attrManager->getFieldValueArr($collid, $traitID, $fieldName, $tidFilter);
										foreach($valueArr as $v){
											if($v) echo '<option>'.$v.'</option>';
										}
										?>
									</select>
								</div>
								<div style="margin:5px;">
									<?php 
									$traitArr = $attrManager->getTraitArr($traitID);
									$controlType = 'checkbox';
									if($traitArr['props']){
										$propArr = json_decode($traitArr['props']);
										if(isset($propArr['controlType'])) $controlType = $propArr['controlType'];
									}
									$attrStateArr = $attrManager->getTraitStates($traitID);
									if($controlType == 'checkbox'){
										foreach($attrStateArr as $sid => $sArr){
											echo '<div title="'.$sArr['description'].'"><input name="stateid[]" type="checkbox" value="'.$sid.'" /> '.$sArr['name'].'</div>';
										}
									}
									elseif($controlType == 'radio'){
										foreach($attrStateArr as $sid => $sArr){
											echo '<div title="'.$sArr['description'].'"><input name="stateid[]" type="radio" value="'.$sid.'" /> '.$sArr['name'].'</div>';
										}
									}
									elseif($controlType == 'select'){
										echo '<select name="stateid">';
										echo '<option value="">Select State</option>';
										echo '<option value="">------------------------------</option>';
										foreach($attrStateArr as $sid => $sArr){
											echo '<option value="'.$sid.'">'.$sArr['name'].'</option>';
										}
										echo '</select>';
									}
									?>
								</div>
								<div style="margin:10px;">
									Notes: <input name="notes" type="text" style="width:350px" value="" /> 
								</div>
								<div style="margin:5px;">
									<input name="taxonfilter" type="hidden" value="<?php echo $taxonFilter; ?>" />
									<input name="tidfilter" type="hidden" value="<?php echo $tidFilter; ?>" />
									<input name="traitid" type="hidden" value="<?php echo $traitID; ?>" />
									<input name="fieldname" type="hidden" value="<?php echo $fieldName; ?>" />
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="submitform" type="submit" value="Batch Assign State" />
								</div>
							</form>
						</fieldset>
					</div>
					<?php
				}
			}
			else{
				echo '<div style="margin:20px"><b>ERROR: Identifier not set</b></div>';
			} 
			?> 
		</div>
	</body>
</html>