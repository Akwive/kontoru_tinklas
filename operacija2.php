<?php
// operacija2.php
// elementari operacija be DB:  rodo paskutinį įvestą vardą
session_start();
?>

<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=9; text/html; charset=utf-8">
        <title>Operacija 2</title>
        <link href="include/styles.css" rel="stylesheet" type="text/css" >
    </head>
    <body>
      <table class="center" ><tr><td> <center><img src="../include/top.png"></center> </td></tr>
		<tr><td>
		
      <table style="border-width: 2px; border-style: dotted;"><tr>
		  <td style="width:50%"> <p align="left">
		  		Atgal į [<a href="index.php">Pradžia</a>]</p>
      	  </td><td>
		   Elementari operacija be DB:  rodo paskutinį įvestą vardą
	</td></tr></table><hr>
		
		<div style="text-align: center;color:green">
            <h1>Operacija 2</h1><hr>
<?php
	if(!empty($_POST) && $_POST['vardas'] != "") $vardas = $_POST['vardas'];
	else $vardas = "Default";
	echo '<p style="font-size:25px">';
		echo "Galiojantis vardas: ";
		echo '<span style="color:red">'.$vardas."</span><br>";
	echo '</p>';
?>
			<center><h3>Įveskite naują vardą</h3></center>		
			<form method='post'>
     			<input name='vardas' type='text' maxlength="10"><br><br>
    			<input type='submit' name='ok' value='Patvirtinti'>
			</form>
        </div><br>
	  </td></tr></table>
	</body>
</html>
		